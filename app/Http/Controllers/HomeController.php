<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\Forum;
use App\Services\FirewallDecisionService;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * الصفحة الرئيسية
     */
    public function index(FirewallDecisionService $firewall)
    {
        $survivalMode = $firewall->survivalModeActive();

        // تحديد مجموعة المستخدم الحالي لعزل الـ Cache
        $usergroupId = auth()->check() ? (int) auth()->user()->usergroupid : 1;

        // ————————————————————————————————————————
        // مواضيع متنوعة من الأرشيف (عشوائية — تتغير كل ساعة)
        // ملاحظة: استبدلنا inRandomOrder() (بطيء على MySQL الكبيرة)
        // بـ fast random offset — أسرع بـ 100x على shared hosting
        // ————————————————————————————————————————
        $latestThreads = $survivalMode ? collect() : Cache::remember("home_archive_{$usergroupId}_" . date('Y-m-d-H'), 3600, function () {
            // Avoid repeated COUNT(*) on the large thread table during crawler waves.
            $threadCount = Cache::remember('home_visible_thread_count', 3600, fn() => Thread::visible()->count());
            $maxOffset = max(0, $threadCount - 5);
            $offset = $maxOffset > 0 ? random_int(0, $maxOffset) : 0;

            return Thread::visible()
                ->with(['forum', 'author'])
                ->offset($offset)
                ->limit(5)
                ->get();
        });

        // أكثر المواضيع مشاهدة (مخصص حسب المجموعة)
        $popularThreads = $survivalMode ? collect() : Cache::remember("home_popular_{$usergroupId}", 3600, function () {
            return Thread::visible()
                ->mostViewed()
                ->with(['forum', 'author'])
                ->limit(6)
                ->get();
        });

        // ————————————————————————————————————————
        // مواضيع مميزة (عشوائية — تتغير كل ساعة)
        // ————————————————————————————————————————
        $topThreadsYear = $survivalMode ? collect() : Cache::remember("home_featured_{$usergroupId}_" . date('Y-m-d-H'), 3600, function () {
            // Reuse cached visible count; COUNT(*) can be expensive on shared MySQL.
            $threadCount = Cache::remember('home_visible_thread_count', 3600, fn() => Thread::visible()->count());
            $maxOffset = max(0, $threadCount - 5);
            $offset = $maxOffset > 0 ? random_int(0, $maxOffset) : 0;

            return Thread::visible()
                ->with(['author'])
                ->offset($offset)
                ->limit(5)
                ->get();
        });

        // الأقسام الرئيسية (مخصصة حسب المجموعة)
        $forums = Cache::remember("home_forums_{$usergroupId}", 1800, function () {
            return Forum::active()
                ->accessible()
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

        // إحصائيات عامة
        // ملاحظة: في وضع البقاء نخزّن الإحصائيات الصفرية لمدة قصيرة فقط (دقيقتان)
        // حتى يتعافى الموقع سريعاً بمجرد انتهاء وضع البقاء، وإلا تبقى الأصفار 6 ساعات.
        $stats = Cache::remember($survivalMode ? 'home_stats_survival' : 'home_stats', $survivalMode ? 120 : 3600, function () use ($survivalMode) {

            if ($survivalMode) {
                return [
                    'threads' => Cache::get('home_visible_thread_count', 0),
                    'forums' => 0,
                    'users' => 0,
                    'posts' => 0,
                ];
            }

            return [
                'threads' => Thread::visible()->count(),
                'forums' => Forum::active()->count(),
                'users' => \App\Models\User::count(),
                'posts' => \App\Models\Post::visible()->count(),
            ];
        });

        return view('home', compact('latestThreads', 'popularThreads', 'forums', 'stats', 'topThreadsYear', 'survivalMode'));
    }

    /**
     * دالة مساعدة لتدوير الكاش يومياً بشكل مضمون.
     * تقوم بحذف كاش الأمس إن وُجد، وإنشاء كاش اليوم.
     */
    private function dailyCache(string $prefix, string $today, callable $callback)
    {
        $todayKey = "{$prefix}_{$today}";

        // حذف كاش الأمس لضمان عدم تراكم البيانات القديمة
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        Cache::forget("{$prefix}_{$yesterday}");

        // أيضاً حذف أي مفتاح قديم بدون تاريخ (من التحديثات السابقة)
        Cache::forget($prefix);

        return Cache::remember($todayKey, 86400, $callback);
    }
}



