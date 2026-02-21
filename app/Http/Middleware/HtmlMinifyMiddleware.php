<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HtmlMinifyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // قم بالتصغير فقط إذا كان الرد ناجحاً وهو عبارة عن HTML
        if ($this->shouldMinify($response)) {
            $html = $response->getContent();
            $minifiedHtml = $this->minify($html);
            $response->setContent($minifiedHtml);
        }

        return $response;
    }

    /**
     * التحقق مما إذا كان الرد يحتاج للتصغير
     */
    protected function shouldMinify(Response $response): bool
    {
        if (!$response->isSuccessful()) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type');

        // تطبيق التصغير فقط على ملفات HTML الصافية
        if ($contentType && str_contains($contentType, 'text/html')) {
            return true;
        }

        return false;
    }

    /**
     * خوارزمية ضغط الكود الآمنة
     */
    protected function minify(string $html): string
    {
        $search = [
            '/\>[^\S ]+/s',     // Strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // Strip whitespaces before tags, except space
            '/(\s)+/s',         // Shorten multiple whitespace sequences
            '/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', // Remove HTML comments (except IE conditionals)
        ];

        $replace = [
            '>',
            '<',
            '\\1',
            '',
        ];

        // حماية أكواد الـ CSS و الـ JS من التلف أثناء الضغط
        $html = preg_replace_callback('/<(script|style|textarea|pre)[^>]*>.*?<\/\1>/is', function ($matches) {
            return $matches[0];
        }, $html);

        $minified = preg_replace($search, $replace, $html);

        return $minified;
    }
}
