<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\Forum;
use App\Models\User;

class RedirectController extends Controller
{
    /**
     * تحويل رابط showthread.php القديم
     * showthread.php?t=123 → /thread/123/slug
     */
    public function showThread(Request $request)
    {
        // Case 1: Post ID (p) provided
        if ($request->has('p')) {
            $postId = $request->input('p');
            $post = \App\Models\Post::with('thread')->find($postId);

            if ($post && $post->thread) {
                // Determine page number if needed, but for now redirect to thread start
                // or ideally calculate page: ceil($post->position / $perPage)
                return redirect($post->thread->url, 301);
            }
        }

        // Case 2: Thread ID (t) provided
        $id = $request->input('t');

        if ($id) {
            $thread = Thread::find($id);
            if ($thread) {
                return redirect($thread->url, 301);
            }
        }

        abort(404);
    }

    /**
     * تحويل رابط forumdisplay.php القديم
     * forumdisplay.php?f=5 → /forum/5/slug
     */
    public function forumDisplay(Request $request)
    {
        $id = $request->input('f');
        if (!$id)
            abort(404);

        $forum = Forum::find($id);
        if (!$forum)
            abort(404);

        return redirect($forum->url, 301);
    }

    /**
     * تحويل رابط member.php القديم
     * member.php?u=10 → /user/10
     */
    public function member(Request $request)
    {
        $id = $request->input('u');
        if (!$id)
            abort(404);

        $user = User::find($id);
        if (!$user)
            abort(404);

        return redirect($user->url, 301);
    }

    /**
     * تحويل روابط أرشيف المواضيع القديمة
     * /archive/index.php/t-{id}.html → /thread/{id}/{slug}
     *
     * SEO: 301 ينقل 100% من Link Equity إلى الرابط الجديد.
     * يمنع Google من فهرسة رابط الأرشيف كصفحة منفصلة (duplicate content).
     */
    public function archiveThread(int $id)
    {
        $thread = Thread::find($id);
        if (!$thread) {
            abort(404);
        }

        return redirect($thread->url, 301);
    }

    /**
     * تحويل روابط أرشيف الأقسام القديمة
     * /archive/index.php/f-{id}.html → /forum/{id}/{slug}
     */
    public function archiveForum(int $id)
    {
        $forum = Forum::find($id);
        if (!$forum) {
            abort(404);
        }

        return redirect($forum->url, 301);
    }

    /**
     * تحويل روابط العلامات القديمة
     * /tags.php?tag=keyword → /search?q=keyword
     *
     * SEO: يحافظ على link equity من الباك لينكات القديمة.
     * الأمان: يُنظّف المُدخل ويمنع parameter pollution.
     */
    public function tags(Request $request)
    {
        $tag = $request->input('tag');

        // لا يوجد tag → 404
        if (!$tag || !is_string($tag)) {
            abort(404);
        }

        // تنظيف: أخذ أول 100 حرف فقط + إزالة أحرف خطرة
        $tag = mb_substr(trim($tag), 0, 100);
        $tag = preg_replace('/[<>"\'\\\\]/', '', $tag);

        if (empty($tag)) {
            abort(404);
        }

        // 301 → صفحة البحث
        return redirect('/search?' . http_build_query(['q' => $tag]), 301);
    }
}

