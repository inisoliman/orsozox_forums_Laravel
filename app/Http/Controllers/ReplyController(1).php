<?php

namespace App\Http\Controllers;

use App\Helpers\HtmlSanitizer;
use App\Http\Requests\PostReplyRequest;
use App\Models\ForumPermission;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Services\LocalAI\SpamShieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ReplyController extends Controller
{
    public function __construct(private readonly SpamShieldService $spamShield)
    {
    }

    public function store(PostReplyRequest $request, int $id): RedirectResponse
    {
        $thread = Thread::visible()->findOrFail($id);
        $user = $request->user();
        $this->ensureReplyAllowed($thread, (int) $user->usergroupid);

        $content = HtmlSanitizer::clean($request->validated('pagetext'));
        abort_if(trim(strip_tags($content)) === '', 422, 'محتوى الرد مطلوب.');

        $spamScore = $this->spamShield->calculateSpamScore($thread->title, $content);
        $post = $this->createReply($thread, $user, $content, $spamScore);

        return redirect()->to($thread->url . '#post-' . $post->postid)
            ->with('success', $post->visible ? 'تمت إضافة الرد.' : 'تم استلام الرد وسيظهر بعد المراجعة.');
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
            'ipaddress' => request()->ip(),
        ];
    }

    private function recordVisibleReply(Thread $thread, User $user, Post $post): void
    {
        Thread::whereKey($thread->threadid)->update([
            'replycount' => DB::raw('replycount + 1'),
            'lastpost' => $post->dateline,
            'lastposterid' => $user->userid,
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

        $isModerator = in_array($usergroupId, config('forum.admin_usergroup_ids', [5, 6, 7]), true);
        abort_unless($thread->open || $isModerator, 423, 'الموضوع مغلق.');
    }

}
