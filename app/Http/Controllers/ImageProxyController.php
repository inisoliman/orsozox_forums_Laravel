<?php

namespace App\Http\Controllers;

use App\Models\ImageCache;
use App\Services\ImageValidationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageProxyController extends Controller
{
    /**
     * Proxy an external image through our server.
     * Route: GET /image-proxy/{hash}
     */
    public function show(string $hash, ImageValidationService $validator)
    {
        // 1. Find the cached record by hash
        $cached = ImageCache::where('url_hash', $hash)->first();

        if (!$cached) {
            return $this->placeholder();
        }

        $url = $cached->original_url;

        // 2. If pending or stale, validate first
        if ($cached->status === 'pending' || !$cached->isFresh()) {
            $cached = $validator->validate($url);
        }

        // 3. If broken → placeholder
        if ($cached->status === 'broken') {
            return $this->placeholder();
        }

        // 4. Stream the valid image to the client
        return $this->streamImage($url);
    }

    /**
     * Stream an external image to the client.
     */
    private function streamImage(string $url)
    {
        try {
            $response = Http::withOptions([
                'timeout' => 10,
                'connect_timeout' => 5,
                'allow_redirects' => ['max' => 3],
                'verify' => false,
            ])->get($url);

            if (!$response->successful()) {
                return $this->placeholder();
            }

            $body = $response->body();
            $type = $response->header('Content-Type') ?? 'image/jpeg';

            if (!str_starts_with($type, 'image/')) {
                return $this->placeholder();
            }

            return response($body, 200, [
                'Content-Type' => $type,
                'Content-Length' => strlen($body),
                'Cache-Control' => 'public, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]);

        } catch (\Throwable $e) {
            Log::debug('LIIMS proxy failed', ['url' => $url, 'error' => $e->getMessage()]);
            return $this->placeholder();
        }
    }

    /**
     * Return the placeholder image.
     */
    private function placeholder()
    {
        $path = public_path('images/image-unavailable.png');

        if (!file_exists($path)) {
            // Fallback: 1x1 transparent PNG
            $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            return response($pixel, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
