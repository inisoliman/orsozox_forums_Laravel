<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Forum;
use App\Models\Thread;
use App\Models\ForumPermission;
use Illuminate\Support\Facades\Cache;

class ForumController extends Controller
{
    /**
     * عرض قسم معين مع مواضيعه
     */
    public function show(int $id, ?string $slug = null)
    {
        // جلب القسم (عام، مخزن مؤقتاً)
        $forum = Cache::remember("forum_{$id}", 1800, function () use ($id) {
            return Forum::with([
                'children' => function ($q) {
                    $q->active()->ordered()->withCount('threads');
                }
            ])->findOrFail($id);
        });

        // التحقق من صلاحية الوصول للمستخدم الحالي
        $usergroupId = auth()->check() ? (int) auth()->user()->usergroupid : 1;
        if (!ForumPermission::canView($forum->forumid, $usergroupId)) {
            return response()->view('errors.forbidden', [
                'title' => 'هذا القسم للأعضاء المصرح لهم فقط',
                'message' => 'ليس لديك صلاحية للوصول إلى قسم "' . $forum->title . '". قد يكون مخصصاً لفئة معينة من الأعضاء.',
            ]);
        }

        // إعادة التوجيه للرابط الصحيح
        if ($slug !== $forum->slug) {
            return redirect()->route('forum.show', [
                'id' => $forum->forumid,
                'slug' => $forum->slug,
            ], 301);
        }

        $threads = Thread::where('forumid', $id)
            ->visible()
            ->orderBy('sticky', 'desc')
            ->orderBy('dateline', 'desc')
            ->with(['author'])
            ->paginate(20);

        return view('forum.show', compact('forum', 'threads'));
    }
}
