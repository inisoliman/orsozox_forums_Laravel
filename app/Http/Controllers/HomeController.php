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

        // أحدث المواضيع (مخصص حسب المجموعة لتجنب إظهار مواضيع في أقسام محجوبة)
        $latestThreads = Cache::remember("home_latest_{$usergroupId}", 600, function () {
            return Thread::visible()
                ->orderBy('dateline', 'desc')
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

        return view('home', compact('latestThreads', 'popularThreads', 'forums', 'stats'));
    }
}
