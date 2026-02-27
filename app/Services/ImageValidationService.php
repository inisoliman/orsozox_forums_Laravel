<?php

namespace App\Services;

use App\Models\ImageCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageValidationService
{
    /**
     * Private/reserved IP ranges to block (SSRF protection).
     */
    private const BLOCKED_RANGES = [
        '127.0.0.0/8',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '169.254.0.0/16',
        '0.0.0.0/8',
        '100.64.0.0/10',
        '::1/128',
        'fc00::/7',
        'fe80::/10',
    ];

    /**
     * Validate an image URL (uses cache with 24h TTL).
     * Returns the ImageCache record.
     */
    public function validate(string $url): ImageCache
    {
        $hash = ImageCache::hashUrl($url);

        // Check cache first
        $cached = ImageCache::where('url_hash', $hash)->first();
        if ($cached && $cached->isFresh()) {
            return $cached;
        }

        // Perform actual validation
        $result = $this->performCheck($url);

        // Upsert into cache
        return ImageCache::updateOrCreate(
            ['url_hash' => $hash],
            [
                'original_url' => $url,
                'status' => $result['status'],
                'response_code' => $result['response_code'],
                'content_type' => $result['content_type'],
                'content_length' => $result['content_length'],
                'last_checked_at' => now(),
            ]
        );
    }

    /**
     * Perform the actual HEAD request to check the image.
     */
    public function performCheck(string $url): array
    {
        $default = [
            'status' => 'broken',
            'response_code' => null,
            'content_type' => null,
            'content_length' => null,
        ];

        // 1. Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $default;
        }

        // 2. Only allow http/https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array(strtolower($scheme ?? ''), ['http', 'https'])) {
            return $default;
        }

        // 3. SSRF Protection — resolve hostname and check
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host || $this->isBlockedHost($host)) {
            return $default;
        }

        try {
            $response = Http::withOptions([
                'timeout' => 5,
                'connect_timeout' => 3,
                'allow_redirects' => ['max' => 3],
                'verify' => false, // shared hosting SSL issues
            ])->head($url);

            $statusCode = $response->status();
            $contentType = $response->header('Content-Type') ?? '';
            $contentLen = (int) ($response->header('Content-Length') ?? 0);

            // Must be 2xx and image/* MIME type
            $isImage = str_starts_with($contentType, 'image/');
            $isOk = $statusCode >= 200 && $statusCode < 400;
            $sizeOk = $contentLen === 0 || $contentLen <= 5 * 1024 * 1024; // 5MB max (0 = unknown)

            return [
                'status' => ($isOk && $isImage && $sizeOk) ? 'valid' : 'broken',
                'response_code' => $statusCode,
                'content_type' => substr($contentType, 0, 100),
                'content_length' => $contentLen ?: null,
            ];
        } catch (\Throwable $e) {
            Log::channel('single')->debug('LIIMS: Image check failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }

    /**
     * Check if a hostname resolves to a blocked (private) IP.
     */
    private function isBlockedHost(string $host): bool
    {
        // Direct IP check
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPrivateIp($host);
        }

        // Block localhost variants
        if (in_array(strtolower($host), ['localhost', 'localhost.localdomain', '0.0.0.0'])) {
            return true;
        }

        // Resolve hostname
        $ips = gethostbynamel($host);
        if (!$ips) {
            return true; // Can't resolve = treat as blocked
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address falls within private ranges.
     */
    private function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
