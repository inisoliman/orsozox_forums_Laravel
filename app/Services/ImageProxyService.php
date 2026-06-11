<?php

namespace App\Services;

use App\Models\ImageCache;

class ImageProxyService
{
    /**
     * Domains considered "local" — do not proxy these.
     */
    private array $localDomains;

    public function __construct()
    {
        $appHost = parse_url(config('app.url', ''), PHP_URL_HOST) ?: 'orsozox.com';
        $this->localDomains = [
            $appHost,
            'www.' . $appHost,
            // YouTube thumbnail domains — used by YouTube Lite Embed
            'i.ytimg.com',
            'img.youtube.com',
        ];
    }

    /**
     * Transform external image URLs in HTML content to use the proxy.
     * Replaces broken images with placeholders.
     */
    public function transformContent(string $html): string
    {
        if (empty($html)) {
            return $html;
        }

        // Check if proxy is enabled
        $proxyEnabled = app(SettingsService::class)->get('image_proxy_enabled', '0') === '1';
        if (!$proxyEnabled) {
            return $html;
        }

        // Split by code/pre to avoid touching code blocks
        $parts = preg_split('/(<code[^>]*>.*?<\/code>|<pre[^>]*>.*?<\/pre>)/si', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as &$part) {
            if (!preg_match('/^<(code|pre)/i', $part)) {
                $part = $this->processImages($part);
            }
        }

        return implode('', $parts);
    }

    /**
     * Process <img> tags in the content.
     * 
     * Two passes:
     * 1) Linked images: <a href="..."><img src="..."></a>
     *    → If broken: convert to styled clickable button
     *    → If valid: proxy the image, keep the link
     * 2) Standalone images: <img src="...">
     *    → Normal proxy/placeholder logic
     */
    private function processImages(string $html): string
    {
        if (empty($html)) {
            return $html;
        }
        // --- PASS 1: Handle images inside links (<a><img></a>) ---
        $result = preg_replace_callback(
            '#<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>\s*<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>\s*</a>#si',
            function ($matches) {
                $linkUrl = $matches[1];
                $imgSrc = $matches[2];

                // Skip non-external images
                if ($this->shouldSkip($imgSrc)) {
                    return $matches[0];
                }

                // Check image status
                $hash = ImageCache::hashUrl($imgSrc);
                $cached = ImageCache::where('url_hash', $hash)->first();

                if (!$cached) {
                    ImageCache::create([
                        'url_hash' => $hash,
                        'original_url' => $imgSrc,
                        'status' => 'pending',
                    ]);
                }

                // If confirmed broken → convert to styled button with the link
                if ($cached && $cached->status === 'broken' && $cached->isFresh()) {
                    $safeUrl = e($linkUrl);
                    return '<div class="broken-image-wrapper">'
                        . '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" class="broken-image-button">'
                        . 'اضغط هنا لفتح الرابط'
                        . '</a></div>';
                }

                // Valid or pending → proxy the image inside the link
                $proxyUrl = url('/image-proxy/' . $hash);
                $safeLink = e($linkUrl);
                return '<a href="' . $safeLink . '" target="_blank" rel="noopener noreferrer">'
                    . '<img src="' . e($proxyUrl) . '" data-original-src="' . e($imgSrc) . '" loading="lazy" alt="صورة" class="img-fluid bb-img">'
                    . '</a>';
            },
            $html
        );
        // Protect against null return from preg_replace_callback (PCRE backtracking limit)
        $html = $result ?? $html;

        // --- PASS 2: Handle standalone images (not inside links) ---
        $result = preg_replace_callback(
            '/<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>/si',
            function ($matches) {
                $fullTag = $matches[0];
                $src = $matches[1];

                // Skip non-external or already-proxied images
                if ($this->shouldSkip($src)) {
                    return $fullTag;
                }

                // Create hash and ensure record exists
                $hash = ImageCache::hashUrl($src);
                $cached = ImageCache::where('url_hash', $hash)->first();

                if (!$cached) {
                    ImageCache::create([
                        'url_hash' => $hash,
                        'original_url' => $src,
                        'status' => 'pending',
                    ]);
                }

                // If confirmed broken → show placeholder
                if ($cached && $cached->status === 'broken' && $cached->isFresh()) {
                    $placeholder = asset('images/image-unavailable.png?v=' . filemtime(public_path('images/image-unavailable.png')));
                    return '<img src="' . e($placeholder) . '" alt="صورة غير متاحة" loading="lazy" class="missing-image">';
                }

                // Transform to proxy URL
                $proxyUrl = url('/image-proxy/' . $hash);
                return '<img src="' . e($proxyUrl) . '" data-original-src="' . e($src) . '" loading="lazy" alt="صورة" class="img-fluid bb-img">';
            },
            $html
        );
        // Protect against null return from preg_replace_callback (PCRE backtracking limit)
        $html = $result ?? $html;

        return $html;
    }

    /**
     * Check if an image URL should be skipped (local, data URI, already proxied).
     */
    private function shouldSkip(string $src): bool
    {
        if (str_starts_with($src, 'data:'))
            return true;
        if (str_contains($src, '/image-proxy/'))
            return true;
        if ($this->isLocalUrl($src))
            return true;
        if (!preg_match('#^https?://#i', $src))
            return true;
        return false;
    }

    /**
     * Check if a URL points to a local domain.
     */
    private function isLocalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return true;
        }
        return in_array(strtolower($host), $this->localDomains);
    }
}
