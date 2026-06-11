<?php

namespace App\Console\Commands;

use App\Jobs\ScanImagesJob;
use App\Models\ImageCache;
use App\Models\Post;
use App\Services\ImageValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanImagesCommand extends Command
{
    protected $signature = 'images:scan
        {--dry-run : Show what would be scanned without actually scanning}
        {--cleanup : Replace broken images in database (requires image_auto_cleanup=1)}
        {--limit=50 : Maximum number of posts to scan (reduced from 1000 for shared hosting)}
        {--queue : Dispatch as queue jobs instead of synchronous}
        {--delay=500 : Delay in milliseconds between HTTP requests (prevents server overload)}';

    protected $description = 'LIIMS: Scan forum posts for external images and validate their status';

    public function handle(ImageValidationService $validator): int
    {
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $isDryRun = $this->option('dry-run');
        $useQueue = $this->option('queue');

        // Safety: prevent running too many in shared hosting
        if ($limit > 200) {
            $this->warn('⚠️  Limit capped at 200 for shared hosting safety.');
            $limit = 200;
        }

        $this->info('🔍 LIIMS Image Scanner');
        $this->info('=====================');

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE — No changes will be made.');
        }

        $totalImages = 0;
        $validImages = 0;
        $brokenImages = 0;
        $skippedImages = 0;

        $bar = $this->output->createProgressBar($limit);
        $bar->setFormat('verbose');

        // Scan posts in chunks
        Post::select('postid', 'pagetext')
            ->orderBy('postid', 'desc')
            ->limit($limit)
            ->chunk(200, function ($posts) use ($validator, $isDryRun, $useQueue, $delay, $bar, &$totalImages, &$validImages, &$brokenImages, &$skippedImages) {
                $batchUrls = [];

                foreach ($posts as $post) {
                    $text = $post->pagetext ?? '';

                    // Extract image URLs from BBCode [img] tags
                    preg_match_all('/\[img\](https?:\/\/[^\[]+)\[\/img\]/i', $text, $bbMatches);

                    // Extract image URLs from HTML <img> tags
                    preg_match_all('/<img[^>]+src=["\']?(https?:\/\/[^"\'>\s]+)["\']?/i', $text, $htmlMatches);

                    $urls = array_merge($bbMatches[1] ?? [], $htmlMatches[1] ?? []);
                    $urls = array_unique($urls);

                    foreach ($urls as $url) {
                        $url = trim($url);
                        if (empty($url))
                            continue;

                        // Skip local URLs
                        $host = parse_url($url, PHP_URL_HOST);
                        if (!$host || str_contains($host, 'orsozox.com')) {
                            $skippedImages++;
                            continue;
                        }

                        $totalImages++;

                        if ($isDryRun) {
                            $this->line("  📷 Would check: {$url}");
                            continue;
                        }

                        if ($useQueue) {
                            $batchUrls[] = $url;
                        } else {
                            try {
                                $result = $validator->validate($url);
                                if ($result->status === 'valid') {
                                    $validImages++;
                                } else {
                                    $brokenImages++;
                                    $this->line("  ❌ BROKEN: {$url}");
                                }
                                // Rate limiting: sleep between requests
                                if ($delay > 0) {
                                    usleep($delay * 1000);
                                }
                            } catch (\Throwable $e) {
                                $brokenImages++;
                            }
                        }
                    }

                    $bar->advance();
                }

                // Dispatch batch to queue
                if ($useQueue && !empty($batchUrls)) {
                    foreach (array_chunk($batchUrls, 10) as $chunk) {
                        ScanImagesJob::dispatch($chunk);
                    }
                    $this->line('  📤 Dispatched ' . count($batchUrls) . ' URLs to queue.');
                }
            });

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Scan Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total External Images', $totalImages],
                ['Valid Images', $validImages],
                ['Broken Images', $brokenImages],
                ['Skipped (Local)', $skippedImages],
            ]
        );

        if ($isDryRun) {
            $this->warn('Dry run complete. No images were actually checked.');
        } elseif ($useQueue) {
            $this->info('Jobs dispatched to queue. Run `php artisan queue:work --stop-when-empty` to process.');
        }

        return Command::SUCCESS;
    }
}
