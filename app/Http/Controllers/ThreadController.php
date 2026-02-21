<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\ForumPermission;

class ThreadController extends Controller
{
    /**
     * عرض الموضوع مع جميع الردود
     */
    public function show(int $id, ?string $slug = null, \App\Services\ThreadSeoService $seoService)
    {
        $thread = Thread::with(['forum', 'author'])->visible()->findOrFail($id);

        // التحقق من صلاحية الوصول لقسم الموضوع
        $usergroupId = auth()->check() ? (int) auth()->user()->usergroupid : 1;
        if ($thread->forumid && !ForumPermission::canView($thread->forumid, $usergroupId)) {
            $forumTitle = $thread->forum->title ?? 'هذا القسم';
            return response()->view('errors.forbidden', [
                'title' => 'هذا الموضوع في قسم مقيد',
                'message' => 'ليس لديك صلاحية لقراءة المواضيع في قسم "' . $forumTitle . '". يرجى تسجيل الدخول أو التواصل مع الإدارة.',
            ]);
        }

        // إعادة التوجيه للرابط الصحيح
        if ($slug !== $thread->slug) {
            return redirect()->route('thread.show', [
                'id' => $thread->threadid,
                'slug' => $thread->slug,
            ], 301);
        }

        // زيادة عدد المشاهدات
        Thread::where('threadid', $id)->increment('views');

        // الردود مع ترقيم
        $posts = $thread->posts()
            ->visible()
            ->chronological()
            ->with(['author', 'attachments'])
            ->paginate(15);

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

        return view('thread.show', compact('thread', 'posts', 'nextThread', 'prevThread', 'seoData'));
    }
}
