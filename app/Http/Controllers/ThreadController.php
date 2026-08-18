<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Models\ForumPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThreadController extends Controller
{
    /**
     * عرض الموضوع مع جميع الردود
     */
    public function show(\App\Services\ThreadSeoService $seoService, int $id, ?string $slug = null)
    {
        // Load SEO-related relations up front to avoid hidden lazy queries in ThreadSeoService.
        $thread = Thread::with(['forum', 'author', 'firstPost.attachments'])->visible()->findOrFail($id);

        // التحقق من صلاحية الوصول لقسم الموضوع
        $usergroupId = auth()->check() ? (int) auth()->user()->usergroupid : 1;
        if ($thread->forumid && !ForumPermission::canView($thread->forumid, $usergroupId)) {
            $forumTitle = $thread->forum->title ?? 'هذا القسم';
            return response()->view('errors.forbidden', [
                'title' => 'هذا الموضوع في قسم مقيد',
                'message' => 'ليس لديك صلاحية لقراءة المواضيع في قسم "' . $forumTitle . '". يرجى تسجيل الدخول أو التواصل مع الإدارة.',
            ], 403);
        }

        // إعادة التوجيه للرابط الصحيح
        $correctSlug = $thread->slug;
        if (empty($slug) || $slug !== $correctSlug) {
            $redirectParams = ['id' => $thread->threadid];
            // Only add slug if it's not empty, otherwise routing might fail if the route demands it (wait, web.php has {slug?} so it's fine)
            if (!empty($correctSlug)) {
                $redirectParams['slug'] = $correctSlug;
            }

            $redirectUrl = route('thread.show', $redirectParams);

            $qs = request()->getQueryString();
            if (!empty($qs)) {
                $redirectUrl .= '?' . $qs;
            }

            return redirect($redirectUrl, 301);
        }

        // زيادة عدد المشاهدات — مُجمَّعة في الكاش (بدلاً من كتابة DB مباشرة)
        $cacheKey = "views_thread_{$id}";
        $current = (int) Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $current + 1, 600); // 10 دقائق

        // الردود مع ترقيم
        $posts = $thread->posts()
            ->visible()
            ->chronological()
            ->with(['author', 'attachments'])
            // Avoid COUNT(*) on the large post table; next/previous links are enough here.
            ->simplePaginate(15);

        // الموضوع التالي والسابق في نفس القسم
        $nextThread = Thread::where('forumid', $thread->forumid)
            ->where('threadid', '>', $thread->threadid)
            ->visible()
            ->orderBy('threadid', 'asc')
            ->select('threadid', 'title')
            ->first();

        $prevThread = Thread::where('forumid', $thread->forumid)
            ->where('threadid', '<', $thread->threadid)
            ->visible()
            ->orderBy('threadid', 'desc')
            ->select('threadid', 'title')
            ->first();

        // توليد الـ SEO Object باستخدام الـ Service Layer
        $seoData = $seoService->generate($thread);
        $canReply = $this->canReply($thread);

        return view('thread.show', compact('thread', 'posts', 'nextThread', 'prevThread', 'seoData', 'canReply'));
    }

    private function canReply(Thread $thread): bool
    {
        $user = auth()->user();
        if (!$user || !$thread->forumid) {
            return false;
        }

        $usergroupId = (int) $user->usergroupid;

        return ($thread->open || $user->is_admin || $user->is_moderator)
            && ForumPermission::canReply($thread->forumid, $usergroupId);
    }

    /**
     * إرجاع جزء الردود (مستعمًى لصفحة معينة) كـ HTML.
     * يُستخدم بعد إضافة رد عبر AJAX عندما يقع الرد في صفحة أحدث،
     * ليأخذ المتصفح الردود الكاملة لتلك الصفحة بدون إعادة تحميل الصفحة.
     */
    public function postsFragment(Request $request, int $id): JsonResponse
    {
        $thread = Thread::with(['forum', 'author', 'firstPost.attachments'])->visible()->findOrFail($id);

        $usergroupId = auth()->check() ? (int) auth()->user()->usergroupid : 1;
        if ($thread->forumid && !ForumPermission::canView($thread->forumid, $usergroupId)) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        $page = $request->integer('page', 1);

        $posts = $thread->posts()
            ->visible()
            ->chronological()
            ->with(['author', 'attachments'])
            ->simplePaginate(15, ['*'], 'page', max(1, $page));

        $startNumber = ($posts->currentPage() - 1) * $posts->perPage() + 1;

        $html = '';
        foreach ($posts as $index => $post) {
            $html .= view('thread.partials.post', [
                'post' => $post,
                'thread' => $thread,
                'postNumber' => $startNumber + $index,
                'isFirst' => $posts->currentPage() == 1 && $index === 0,
            ])->render();
        }

        return response()->json([
            'success' => true,
            'html' => $html,
            'page' => $posts->currentPage(),
            'has_more' => $posts->hasMorePages(),
        ]);
    }

}
