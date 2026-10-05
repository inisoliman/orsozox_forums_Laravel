<?php

namespace App\Http\Controllers;

use App\Helpers\HtmlSanitizer;
use App\Http\Requests\PostReplyRequest;
use App\Models\ForumPermission;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Services\LocalAI\SpamShieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReplyController extends Controller
{
    private const POSTS_PER_PAGE = 15;

    public function __construct(private readonly SpamShieldService $spamShield)
    {
    }

    public function store(PostReplyRequest $request, int $id)
    {
        $thread = Thread::visible()->findOrFail($id);
        $user = $request->user();
        $this->ensureReplyAllowed($thread, (int) $user->usergroupid);

        $content = HtmlSanitizer::clean($request->validated('pagetext'));
        abort_if(trim(strip_tags($content)) === '', 422, 'محتوى الرد مطلوب.');

        $spamScore = $this->spamShield->calculateSpamScore($thread->title, $content);
        $post = $this->createReply($thread, $user, $content, $spamScore);

        if ($request->wantsJson()) {
            return $this->jsonResponse($thread, $post);
        }

        return redirect()->to($thread->url . '#post-' . $post->postid)
            ->with('success', $post->visible ? 'تمت إضافة الرد.' : 'تم استلام الرد وسيظهر بعد المراجعة.');
    }

    private function jsonResponse(Thread $thread, Post $post): JsonResponse
    {
        $post->load(['author', 'attachments']);

        $totalVisible = $thread->posts()->visible()->count();
        $lastPage = (int) max(1, ceil($totalVisible / self::POSTS_PER_PAGE));
        $thisPage = (int) max(1, ceil($this->countVisibleUpTo($thread, $post->dateline) / self::POSTS_PER_PAGE));

        $postHtml = view('thread.partials.post', [
            'post' => $post,
            'thread' => $thread,
            'postNumber' => $this->countVisibleUpTo($thread, $post->dateline),
            'isFirst' => false,
        ])->render();

        $targetUrl = $thread->url . ($thisPage > 1 ? '?page=' . $thisPage : '') . '#post-' . $post->postid;

        return response()->json([
            'success' => true,
            'visible' => (bool) $post->visible,
            'message' => $post->visible ? 'تمت إضافة الرد.' : 'تم استلام الرد وسيظهر بعد المراجعة.',
            'post' => [
                'postid' => $post->postid,
            ],
            'html' => $postHtml,
            'url' => $targetUrl,
            'post_page' => $thisPage,
            'last_page' => $post->visible ? $lastPage : $thisPage,
        ]);
    }

    private function countVisibleUpTo(Thread $thread, int $dateline): int
    {
        return (int) $thread->posts()
            ->visible()
            ->where('dateline', '<=', $dateline)
            ->count();
    }

    private function createReply(Thread $thread, User $user, string $content, int $spamScore): Post
    {
        return DB::transaction(function () use ($thread, $user, $content, $spamScore) {
            $post = Post::create($this->replyAttributes($thread, $user, $content, $spamScore));

            if ($post->visible) {
                $this->recordVisibleReply($thread, $user, $post);
            }

            return $post;
        });
    }

    private function replyAttributes(Thread $thread, User $user, string $content, int $spamScore): array
    {
        return [
            'threadid' => $thread->threadid,
            'userid' => $user->userid,
            'username' => $user->username,
            'pagetext' => '<!-- HTML -->' . $content,
            'dateline' => time(),
            'visible' => $spamScore > 80 ? 0 : 1,
        ];
    }

    private function recordVisibleReply(Thread $thread, User $user, Post $post): void
    {
        Thread::whereKey($thread->threadid)->update([
            'replycount' => DB::raw('replycount + 1'),
            'lastpost' => $post->dateline,
        ]);
        $user->increment('posts');
    }

    private function ensureReplyAllowed(Thread $thread, int $usergroupId): void
    {
        abort_unless(
            $thread->forumid
            && ForumPermission::canViewAndReply($thread->forumid, $usergroupId),
            403
        );

        $canManage = app(\App\Services\ModerationPermissionService::class)
            ->canManageForum(auth()->user(), (int) $thread->forumid);
        abort_unless($thread->open || $canManage, 423, 'الموضوع مغلق.');
    }

}
