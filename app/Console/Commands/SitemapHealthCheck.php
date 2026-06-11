<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SitemapHealthCheck extends Command
{
    protected $signature = 'sitemap:health {--url= : Sitemap URL, defaults to APP_URL/sitemap.xml}';

    protected $description = 'Lightweight sitemap health check for Google Search Console compatibility.';

    public function handle(): int
    {
        $url = $this->option('url') ?: rtrim((string) config('app.url'), '/') . '/sitemap.xml';

        $this->info('Checking sitemap: ' . $url);

        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                    'Accept' => 'application/xml,text/xml,*/*',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            $this->error('Request failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $status = $response->status();
        $contentType = $response->header('Content-Type', '');
        $body = $response->body();

        $this->line('Status      : ' . $status);
        $this->line('Content-Type: ' . $contentType);
        $this->line('Size        : ' . number_format(strlen($body)) . ' bytes');

        $ok = true;
        if ($status !== 200) {
            $this->error('✗ Sitemap must return HTTP 200.');
            $ok = false;
        }

        if (!str_contains(strtolower($contentType), 'xml')) {
            $this->warn('⚠ Content-Type should be application/xml or text/xml.');
        }

        if (!str_starts_with(ltrim($body), '<?xml')) {
            $this->error('✗ XML declaration missing or response is not XML.');
            $ok = false;
        }

        if (!str_contains($body, '<sitemapindex') && !str_contains($body, '<urlset')) {
            $this->error('✗ XML does not contain sitemapindex/urlset root.');
            $ok = false;
        }

        $this->line('Result      : ' . ($ok ? 'OK for Google Search Console' : 'Needs attention'));

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}