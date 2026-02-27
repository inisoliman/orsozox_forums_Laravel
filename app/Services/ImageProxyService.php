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
     */
    private function processImages(string $html): string
    {
        return preg_replace_callback(
            '/<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>/si',
            function ($matches) {
                $fullTag = $matches[0];
                $src = $matches[1];

                // Skip: data URIs
                if (str_starts_with($src, 'data:')) {
                    return $fullTag;
                }

                // Skip: already proxied
                if (str_contains($src, '/image-proxy/')) {
                    return $fullTag;
                }

                // Skip: local images
                if ($this->isLocalUrl($src)) {
                    return $fullTag;
                }

                // Skip: relative paths (local assets)
                if (!preg_match('#^https?://#i', $src)) {
                    return $fullTag;
                }

                // Create hash and ensure record exists in image_cache
                $hash = ImageCache::hashUrl($src);
                $cached = ImageCache::where('url_hash', $hash)->first();

                if (!$cached) {
                    // Create a pending record so the controller can look up the original URL
                    ImageCache::create([
                        'url_hash' => $hash,
                        'original_url' => $src,
                        'status' => 'pending',
                    ]);
                }

                // If confirmed broken and fresh → show placeholder
                if ($cached && $cached->status === 'broken' && $cached->isFresh()) {
                    $placeholder = asset('images/image-unavailable.png');
                    return '<img src="' . e($placeholder) . '" alt="صورة غير متاحة" loading="lazy" class="missing-image">';
                }

                // Transform to proxy URL
                $proxyUrl = url('/image-proxy/' . $hash);

                return '<img src="' . e($proxyUrl) . '" data-original-src="' . e($src) . '" loading="lazy" alt="صورة" class="img-fluid bb-img">';
            },
            $html
        );
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
