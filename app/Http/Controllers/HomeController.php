<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\Forum;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * الصفحة الرئيسية
     */
    public function index()
    {
        // تحديد مجموعة المستخدم الحالي لعزل الـ Cache
        $usergroupId = auth()->check() ? (int) auth()->user()->usergroupid : 1;

        // مواضيع متنوعة من الأرشيف (عشوائية تماماً من جميع المواضيع منذ بداية الموقع)
        $latestThreads = Cache::remember("home_latest_rand_{$usergroupId}", 86400, function () {
            return Thread::visible()
                ->inRandomOrder()
                ->with(['forum', 'author'])
                ->limit(12)
                ->get();
        });

        // أكثر المواضيع مشاهدة (مخصص حسب المجموعة)
        $popularThreads = Cache::remember("home_popular_{$usergroupId}", 3600, function () {
            return Thread::visible()
                ->mostViewed()
                ->with(['forum', 'author'])
                ->limit(6)
                ->get();
        });

        // أبرز المواضيع (تتغير عشوائياً كل 24 ساعة من جميع مواضيع المنتدى ذات التفاعل/المشاهدات)
        $topThreadsYear = Cache::remember("home_topyear_rand_{$usergroupId}", 86400, function () {
            return Thread::visible()
                ->where('views', '>', 50) // فلتر بسيط لضمان أن الموضوع ليس فارغاً تماماً
                ->inRandomOrder()
                ->with(['author'])
                ->limit(5)
                ->get();
        });

        // الأقسام الرئيسية (مخصصة حسب المجموعة — لا نخلط بين الأعضاء والزوار في الـ Cache)
        $forums = Cache::remember("home_forums_{$usergroupId}", 1800, function () {
            return Forum::active()
                ->accessible()   // يُطبق تصاريح usergroupid للمستخدم الحالي
                ->root()
                ->ordered()
                ->with([
                    'children' => function ($q) {
                        $q->active()->accessible()->ordered()->withCount('threads')->with([
                            'children' => function ($q2) {
                                $q2->active()->accessible()->ordered()->withCount('threads');
                            }
                        ]);
                    }
                ])
                ->withCount('threads')
                ->get();
        });

        // إحصائيات عامة (لا تحتاج إلى تخصيص)
        $stats = Cache::remember('home_stats', 3600, function () {
            return [
                'threads' => Thread::visible()->count(),
                'forums' => Forum::active()->count(),
                'users' => \App\Models\User::count(),
            ];
        });

        return view('home', compact('latestThreads', 'popularThreads', 'forums', 'stats', 'topThreadsYear'));
    }
}
