<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Models\Forum;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Sitemap Index — يُقسّم إلى ملفات متعددة
     * /sitemap.xml
     */
    public function index()
    {
        $threadCount = Cache::remember('sitemap_thread_count', 3600, fn() => Thread::visible()->count());
        $perPage = 1000;
        $pages = max(1, (int) ceil($threadCount / $perPage));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Sitemap الأقسام
        $xml .= "  <sitemap>\n";
        $xml .= '    <loc>' . htmlspecialchars(route('sitemap.forums')) . "</loc>\n";
        $xml .= '    <lastmod>' . now()->toW3cString() . "</lastmod>\n";
        $xml .= "  </sitemap>\n";

        // أعداد الأعضاء
        $userCount = Cache::remember('sitemap_user_count', 3600, fn() => \App\Models\User::count());
        $userPages = max(1, (int) ceil($userCount / $perPage));

        // Sitemap الأعضاء (بصفحات)
        for ($i = 1; $i <= $userPages; $i++) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . htmlspecialchars(route('sitemap.users', ['page' => $i])) . "</loc>\n";
            $xml .= '    <lastmod>' . now()->toW3cString() . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        // Sitemap المواضيع (بصفحات)
        for ($i = 1; $i <= $pages; $i++) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . htmlspecialchars(route('sitemap.threads', ['page' => $i])) . "</loc>\n";
            $xml .= '    <lastmod>' . now()->toW3cString() . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Sitemap الأقسام
     * /sitemap-forums.xml
     */
    public function forums()
    {
        $xml = Cache::remember('sitemap_forums_xml', 86400, function () {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // الصفحة الرئيسية
            $xml .= $this->urlTag(url('/'), now()->toW3cString(), 'daily', '1.0');

            // الأقسام
            $forums = Forum::active()->get();
            foreach ($forums as $forum) {
                $xml .= $this->urlTag($forum->url, now()->toW3cString(), 'daily', '0.8');
            }

            $xml .= '</urlset>';
            return $xml;
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Sitemap المواضيع — مُقسَّم بصفحات
     * /sitemap-threads-{page}.xml
     */
    public function threads(int $page = 1)
    {
        $perPage = 1000;

        $xml = Cache::remember("sitemap_threads_{$page}", 86400, function () use ($page, $perPage) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            $threads = Thread::visible()
                ->orderBy('dateline', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get(['threadid', 'title', 'lastpost', 'forumid', 'postuserid']);

            foreach ($threads as $thread) {
                // lastmod = تاريخ آخر رد حقيقي
                $lastmod = $thread->last_post_date
                    ? $thread->last_post_date->toW3cString()
                    : now()->toW3cString();

                $xml .= $this->urlTag($thread->url, $lastmod, 'weekly', '0.6');
            }

            $xml .= '</urlset>';
            return $xml;
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Sitemap الأعضاء — مُقسَّم بصفحات
     * /sitemap-users-{page}.xml
     */
    public function users(int $page = 1)
    {
        $perPage = 1000;

        $xml = Cache::remember("sitemap_users_{$page}", 86400, function () use ($page, $perPage) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            $users = \App\Models\User::orderBy('userid', 'asc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get(['userid', 'lastactivity']); // lastactivity is from vBulletin legacy

            foreach ($users as $user) {
                // lastmod is last visit/activity or current date if empty
                $lastmod = $user->last_visit_formatted
                    ? $user->last_visit_formatted->toW3cString()
                    : now()->toW3cString();

                $xml .= $this->urlTag(route('user.show', $user->userid), $lastmod, 'monthly', '0.5');
            }

            $xml .= '</urlset>';
            return $xml;
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }

    private function urlTag(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "  <url>\n" .
            '    <loc>' . htmlspecialchars($loc) . "</loc>\n" .
            "    <lastmod>{$lastmod}</lastmod>\n" .
            "    <changefreq>{$changefreq}</changefreq>\n" .
            "    <priority>{$priority}</priority>\n" .
            "  </url>\n";
    }
}
