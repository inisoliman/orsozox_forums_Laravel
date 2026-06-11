<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Required SEO Security Headers (E-E-A-T trust signals)
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        // 🔍 Do NOT add CSP on XML responses (sitemaps, feeds) — it can confuse crawlers
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'xml') && !str_contains($contentType, 'application/xml')) {
            $headers['Content-Security-Policy'] = $this->buildCsp($request);
        }

        if (method_exists($response, 'header')) {
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }
        } elseif (property_exists($response, 'headers') && method_exists($response->headers, 'set')) {
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }

    /**
     * Build a CSP header that allows the required external resources.
     */
    private function buildCsp(Request $request): string
    {
        // Note: 'unsafe-inline' is needed for our inline scripts.
        // In a future step, move inline scripts to external files and remove 'unsafe-inline'.
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "frame-src https://www.youtube.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
