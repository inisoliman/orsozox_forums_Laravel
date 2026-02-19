<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GzipMiddleware
{
    /**
     * ضغط الاستجابة بـ Gzip إذا كان المتصفح يدعمه
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // تحقق من دعم المتصفح لـ Gzip
        if (!$request->header('Accept-Encoding') || !str_contains($request->header('Accept-Encoding'), 'gzip')) {
            return $response;
        }

        // لا تضغط إذا كانت الاستجابة Binary أو صغيرة
        $content = $response->getContent();
        if (empty($content) || strlen($content) < 1024) {
            return $response;
        }

        // تحقق أن الاستجابة ليست ملف ثنائي
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'image/') || str_contains($contentType, 'video/')) {
            return $response;
        }

        $compressed = gzencode($content, 6);
        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
