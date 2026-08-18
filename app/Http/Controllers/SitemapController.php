<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Models\Forum;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SitemapController extends Controller
{
    /** Cache TTL (seconds) — 6 ساعات. يُعاد توليده يومياً عبر sitemap:warm */
    private const CACHE_TTL = 21600;

    /** Number of thread URLs per sub-sitemap (Google max 50,000) */
    private const PER_PAGE = 1000;

    /**
     * Sitemap Index — يُقسّم إلى ملفات متعددة
     * /sitemap.xml
     */
    public function index()
    {
        $xml = Cache::remember('sitemap_index_xml', self::CACHE_TTL, function () {
            $threadCount = (int) Thread::publiclyIndexable()->count();
            $pages = max(1, (int) ceil($threadCount / self::PER_PAGE));

            // lastmod حقيقي = آخر موضوع تم تعديله/الرد عليه
            $latestLastpost = (int) Thread::publiclyIndexable()->max('lastpost');
            $globalLastmod = $latestLastpost > 0
                ? Carbon::createFromTimestamp($latestLastpost)->toW3cString()
                : now()->toW3cString();

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // Sitemap الأقسام
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . htmlspecialchars(route('sitemap.forums'), ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n";
            $xml .= '    <lastmod>' . $globalLastmod . "</lastmod>\n";
            $xml .= "  </sitemap>\n";

            // Sitemap المواضيع (بصفحات) — lastmod لكل صفحة = أحدث lastpost داخلها
            for ($i = 1; $i <= $pages; $i++) {
                $pageLastpost = (int) Thread::publiclyIndexable()
                    ->orderBy('lastpost', 'desc')
                    ->offset(($i - 1) * self::PER_PAGE)
                    ->value('lastpost');

                $pageLastmod = $pageLastpost > 0
                    ? Carbon::createFromTimestamp($pageLastpost)->toW3cString()
                    : $globalLastmod;

                $xml .= "  <sitemap>\n";
                $xml .= '    <loc>' . htmlspecialchars(route('sitemap.threads', ['page' => $i]), ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n";
                $xml .= '    <lastmod>' . $pageLastmod . "</lastmod>\n";
                $xml .= "  </sitemap>\n";
            }

            $xml .= '</sitemapindex>';
            return $xml;
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-Robots-Tag', 'all')
            ->header('X-UA-Compatible', 'IE=edge');
    }

    /**
     * Sitemap الأقسام
     * /sitemap-forums.xml
     */
    public function forums()
    {
        $xml = Cache::remember('sitemap_forums_xml', self::CACHE_TTL, function () {
            // lastmod ديناميكي = آخر نشاط فعلي على المنتدى
            $latestLastpost = (int) Thread::publiclyIndexable()->max('lastpost');
            $globalLastmod = $latestLastpost > 0
                ? Carbon::createFromTimestamp($latestLastpost)->toW3cString()
                : now()->startOfDay()->toW3cString();

            // الصفحات الثابتة لها lastmod ثابت (يوم البداية) لتجنّب إشارات تغيير زائفة
            $staticLastmod = now()->startOfDay()->toW3cString();

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // الصفحة الرئيسية
            $xml .= $this->urlTag(url('/'), $globalLastmod, 'daily', '1.0');

            // صفحات ثابتة قابلة للفهرسة
            $xml .= $this->urlTag(route('page.about'), $staticLastmod, 'monthly', '0.7');
            $xml .= $this->urlTag(route('page.editorial'), $staticLastmod, 'monthly', '0.7');
            $xml .= $this->urlTag(route('page.privacy'), $staticLastmod, 'monthly', '0.5');
            $xml .= $this->urlTag(route('page.contact'), $staticLastmod, 'monthly', '0.5');

            // الأقسام — lastmod = آخر نشاط داخل القسم
            $forums = Forum::active()
                ->publiclyAccessible()
                ->get(['forumid', 'title', 'parentid', 'options']);
            foreach ($forums as $forum) {
                $forumLastpost = (int) Thread::publiclyIndexable()
                    ->where('forumid', $forum->forumid)
                    ->max('lastpost');

                $forumLastmod = $forumLastpost > 0
                    ? Carbon::createFromTimestamp($forumLastpost)->toW3cString()
                    : $globalLastmod;

                $xml .= $this->urlTag($forum->url, $forumLastmod, 'daily', '0.8');
            }

            $xml .= '</urlset>';
            return $xml;
        });


        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('X-Robots-Tag', 'all');
    }

    /**
     * Sitemap المواضيع — مُقسَّم بصفحات
     * /sitemap-threads-{page}.xml
     */
    public function threads(int $page = 1)
    {
        $perPage = self::PER_PAGE;

        $xml = Cache::remember("sitemap_threads_{$page}", self::CACHE_TTL, function () use ($page, $perPage) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // ⚠️ الترتيب بـ lastpost (آخر نشاط) وليس dateline (تاريخ الإنشاء)
            // هذا يضمن أن المواضيع المعدّلة من قاعدة البيانات تظهر في أعلى الـ sitemap
            $threads = Thread::publiclyIndexable()
                ->orderBy('lastpost', 'desc')
                ->orderBy('threadid', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get(['threadid', 'title', 'lastpost', 'forumid', 'postuserid']);

            foreach ($threads as $thread) {
                // lastmod = تاريخ آخر رد حقيقي
                $lastpostTs = (int) $thread->lastpost;
                $lastmod = $lastpostTs > 0
                    ? Carbon::createFromTimestamp($lastpostTs)->toW3cString()
                    : now()->toW3cString();

                // ⭐ إشارات الحداثة الذكية: priority/changefreq حسب عمر النشاط
                // (يحفز جوجل على فحص المواضيع النشطة دون إلغاء القديمة)
                [$changefreq, $priority] = $this->freshnessSignals($lastpostTs);

                $xml .= $this->urlTag($thread->url, $lastmod, $changefreq, $priority);
            }


            $xml .= '</urlset>';
            return $xml;
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=21600')
            ->header('X-Robots-Tag', 'all');
    }

    /**
     * Clear all sitemap caches — يُستدعى من sitemap:warm
     */
    public static function clearCache(): void
    {
        Cache::forget('sitemap_index_xml');
        Cache::forget('sitemap_forums_xml');
        Cache::forget('sitemap_thread_count');

        // امسح كل صفحات المواضيع (حتى 200 صفحة احتياطاً)
        for ($i = 1; $i <= 200; $i++) {
            Cache::forget("sitemap_threads_{$i}");
        }
    }


    /**
     * Keep the old users sitemap route safe if a cached route/index requests it.
     * User profile pages are marked noindex, so they must not be advertised in sitemap.xml.
     */
    public function users(int $page = 1)
    {
        abort(404);
    }

    private function urlTag(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        $loc = htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        return "  <url>\n" .
            "    <loc>{$loc}</loc>\n" .
            "    <lastmod>{$lastmod}</lastmod>\n" .
            "    <changefreq>{$changefreq}</changefreq>\n" .
            "    <priority>{$priority}</priority>\n" .
            "  </url>\n";
    }

    /**
     * إشارات الحداثة الذكية حسب عمر آخر نشاط في الموضوع.
     *
     * يساعد جوجل على:
     *  - تركيز crawl budget على المواضيع النشطة (≤6 شهور).
     *  - الاحتفاظ بالمواضيع القديمة كمحتوى مرجعي بدون إهدار موارد عليها.
     *
     * @return array{0: string, 1: string} [changefreq, priority]
     */
    private function freshnessSignals(int $lastpostTs): array
    {
        if ($lastpostTs <= 0) {
            return ['yearly', '0.3'];
        }

        $ageDays = (int) floor((time() - $lastpostTs) / 86400);

        if ($ageDays <= 30) {
            // نشط جداً - آخر شهر
            return ['daily', '0.9'];
        }
        if ($ageDays <= 180) {
            // نشط - آخر 6 شهور
            return ['weekly', '0.7'];
        }
        if ($ageDays <= 730) {
            // قديم نسبياً - حتى سنتين
            return ['monthly', '0.5'];
        }
        // أرشيف - أكثر من سنتين
        return ['yearly', '0.3'];
    }

}

