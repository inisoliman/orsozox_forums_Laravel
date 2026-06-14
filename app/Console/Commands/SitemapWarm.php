<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use App\Models\Thread;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * sitemap:warm
 *
 * 1) يمسح كل كاش الـ sitemap القديم.
 * 2) يُعيد توليد:
 *      - sitemap_index_xml
 *      - sitemap_forums_xml
 *      - sitemap_threads_{1..N}
 *    عبر استدعاء داخلي للـ Routes (فيُخزّن نتائجها في الكاش الجديد).
 * 3) (اختياري) يُرسل ping إلى محركات البحث.
 *
 * يُستدعى يومياً من Schedule (راجع routes/console.php)
 * أو يدوياً:  php artisan sitemap:warm
 *             php artisan sitemap:warm --ping
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
        $threadCount = (int) Thread::visible()->count();
        $perPage = 1000;
        $pages = max(1, (int) ceil($threadCount / $perPage));

        $this->line("Threads: {$threadCount} — Sub-sitemaps: {$pages}");

        // 3) Warm caches by hitting our own routes internally
        $base = rtrim((string) config('app.url'), '/');
        $urls = [
            $base . '/sitemap.xml',
            $base . '/sitemap-forums.xml',
        ];
        for ($i = 1; $i <= $pages; $i++) {
            $urls[] = $base . "/sitemap-threads-{$i}.xml";
        }

        $bar = $this->output->createProgressBar(count($urls));
        $bar->start();

        $okCount = 0;
        $failCount = 0;

        foreach ($urls as $url) {
            try {
                $res = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'SitemapWarmer/1.0 (internal)',
                        'Accept' => 'application/xml',
                    ])
                    ->get($url);

                if ($res->status() === 200) {
                    $okCount++;
                } else {
                    $failCount++;
                    $this->newLine();
                    $this->warn("  ⚠ {$url} returned HTTP " . $res->status());
                }
            } catch (\Throwable $e) {
                $failCount++;
                $this->newLine();
                $this->warn("  ⚠ {$url} failed: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // 4) Mark last warmed time (for monitoring)
        Cache::forever('sitemap_last_warmed_at', now()->toIso8601String());

        // 5) Ping search engines (optional)
        if ($this->option('ping')) {
            $this->pingSearchEngines($base . '/sitemap.xml');
        }

        $duration = round(microtime(true) - $start, 2);
        $this->info("✓ Done in {$duration}s — OK: {$okCount}, Failed: {$failCount}");

        return $failCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Ping Bing IndexNow. (Google توقّف عن قبول ping منذ يونيو 2023،
     * أصبح يكتشف sitemap من robots.txt + Search Console فقط.)
     */
    private function pingSearchEngines(string $sitemapUrl): void
    {
        $this->line('Pinging search engines...');

        // Bing IndexNow - بسيط بدون مفتاح للـ sitemap
        try {
            $bing = Http::timeout(10)->get('https://www.bing.com/ping', [
                'sitemap' => $sitemapUrl,
            ]);
            $this->line('  Bing: HTTP ' . $bing->status());
        } catch (\Throwable $e) {
            $this->warn('  Bing ping failed: ' . $e->getMessage());
        }

        $this->comment('  Note: Google no longer supports sitemap ping. Use Search Console.');
    }
}
