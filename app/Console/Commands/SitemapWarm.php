<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use App\Models\Thread;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * sitemap:warm
 *
 * 1) يمسح كل كاش الـ sitemap القديم.
 * 2) يُعيد توليد الـ XML لكل ملف عبر **استدعاء الـ Controller مباشرة**
 *    (داخل نفس عملية PHP - بدون HTTP - فيتجاوز الـ Firewall/RateLimiter).
 * 3) (اختياري) يُرسل ping إلى محركات البحث.
 *
 * الاستخدام:
 *   php artisan sitemap:warm
 *   php artisan sitemap:warm --ping
 *   php artisan sitemap:warm --no-clear   (warm only missing entries)
 */
class SitemapWarm extends Command
{
    protected $signature = 'sitemap:warm
        {--ping : Send ping to Bing/IndexNow after regenerating}
        {--no-clear : Do not clear existing cache, only warm missing entries}';

    protected $description = 'Clear sitemap cache and regenerate it (run daily via scheduler).';

    public function handle(): int
    {
        $start = microtime(true);

        // 1) Clear old cache (unless --no-clear)
        if (!$this->option('no-clear')) {
            $this->info('Clearing old sitemap cache...');
            SitemapController::clearCache();
        }

        // 2) Recompute pages count
        $threadCount = (int) Thread::publiclyIndexable()->count();
        $perPage = 1000;
        $pages = max(1, (int) ceil($threadCount / $perPage));

        $this->line("Threads: {$threadCount} — Sub-sitemaps: {$pages}");
        $this->line('Warming caches via direct controller calls (no HTTP, bypasses firewall)...');

        // 3) Warm caches by calling the controller directly (NO HTTP)
        $controller = app(SitemapController::class);
        $totalSteps = 2 + $pages; // index + forums + N thread pages

        $bar = $this->output->createProgressBar($totalSteps);
        $bar->start();

        $okCount = 0;
        $failCount = 0;
        $errors = [];

        // 3.1) Index
        try {
            $resp = $controller->index();
            $size = strlen((string) $resp->getContent());
            if ($resp->getStatusCode() === 200 && $size > 0) {
                $okCount++;
            } else {
                $failCount++;
                $errors[] = "sitemap.xml: HTTP {$resp->getStatusCode()}";
            }
        } catch (\Throwable $e) {
            $failCount++;
            $errors[] = 'sitemap.xml: ' . $e->getMessage();
        }
        $bar->advance();

        // 3.2) Forums
        try {
            $resp = $controller->forums();
            $size = strlen((string) $resp->getContent());
            if ($resp->getStatusCode() === 200 && $size > 0) {
                $okCount++;
            } else {
                $failCount++;
                $errors[] = "sitemap-forums.xml: HTTP {$resp->getStatusCode()}";
            }
        } catch (\Throwable $e) {
            $failCount++;
            $errors[] = 'sitemap-forums.xml: ' . $e->getMessage();
        }
        $bar->advance();

        // 3.3) Each thread sub-sitemap
        for ($i = 1; $i <= $pages; $i++) {
            try {
                $resp = $controller->threads($i);
                $size = strlen((string) $resp->getContent());
                if ($resp->getStatusCode() === 200 && $size > 0) {
                    $okCount++;
                } else {
                    $failCount++;
                    $errors[] = "sitemap-threads-{$i}.xml: HTTP {$resp->getStatusCode()}";
                }
            } catch (\Throwable $e) {
                $failCount++;
                $errors[] = "sitemap-threads-{$i}.xml: " . $e->getMessage();
            }
            $bar->advance();

            // Free memory between iterations (each page holds ~1000 thread models)
            if ($i % 10 === 0) {
                gc_collect_cycles();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Show errors if any
        if (!empty($errors)) {
            $this->warn('Errors:');
            foreach (array_slice($errors, 0, 10) as $err) {
                $this->line('  ⚠ ' . $err);
            }
            if (count($errors) > 10) {
                $this->line('  ... and ' . (count($errors) - 10) . ' more');
            }
        }

        // 4) Mark last warmed time (for monitoring)
        Cache::forever('sitemap_last_warmed_at', now()->toIso8601String());

        // 5) Ping search engines (optional)
        if ($this->option('ping')) {
            $base = rtrim((string) config('app.url'), '/');
            $this->pingSearchEngines($base . '/sitemap.xml');
        }

        $duration = round(microtime(true) - $start, 2);
        $this->info("✓ Done in {$duration}s — OK: {$okCount}, Failed: {$failCount}");

        return $failCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Ping search engines.
     * - Google: توقّف عن قبول /ping منذ يونيو 2023.
     * - Bing:   أيضاً أرجع HTTP 410 على /ping. الطريق الحديث = IndexNow.
     */
    private function pingSearchEngines(string $sitemapUrl): void
    {
        $this->line('Pinging search engines...');

        // Bing IndexNow (لا حاجة لمفتاح لإرسال sitemap location)
        // Note: Bing /ping endpoint is deprecated (returns 410). Skip it.
        $this->comment('  ℹ Note: Both Google and Bing deprecated /ping?sitemap=...');
        $this->comment('  ℹ Submit sitemap manually in Google Search Console & Bing Webmaster Tools (one-time).');
        $this->comment('  ℹ Search engines will then crawl it on their own schedule based on <lastmod>.');

        // مرجع: للمستقبل لو احتجنا IndexNow الفعلي:
        // https://www.bing.com/indexnow?url=...&key=YOUR_KEY
    }
}
