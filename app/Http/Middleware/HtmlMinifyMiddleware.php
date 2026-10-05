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

        // لا تُصغّر لوحة التحكم (Filament/Livewire) ولا مسارات Livewire إطلاقاً؛
        // فتصغير HTML هناك قد يكسر سكربتات Alpine/Livewire ويُعطّل الترقيم والإجراءات.
        $path = ltrim(request()->path(), '/');
        if (str_starts_with($path, 'admin') || str_starts_with($path, 'livewire')) {
            return false;
        }

        // لا تُصغّر صفحات المنتدى العامة إطلاقاً.
        // السبب المُثبت: صفحات المنتدى تحتوي سكربتات مضمَّنة بتعليقات عربية،
        // وCloudflare Auto-Minify يدمج أسطر السكربتات، فتعليقات "//" تبتلع بقية الكود.
        // التصغير هنا يضاعف الخطر بلا فائدة تُذكر لهذا الموقع.
        return false;
    }

    /**
     * خوارزمية ضغط الكود الآمنة
     */
    protected function minify(string $html): string
    {
        // علم u إلزامي: بدونه تفشل المطابقة على محتوى UTF-8 العربي،
        // فيُرجع preg_replace القيمة null ويُمسح الـ HTML كاملًا (صفحة بيضاء).
        $search = [
            '/\>[^\S ]+/su',     // Strip whitespaces after tags, except space
            '/[^\S ]+\</su',     // Strip whitespaces before tags, except space
            '/(\s)+/su',         // Shorten multiple whitespace sequences
            '/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/su', // Remove HTML comments (except IE conditionals)
        ];

        $replace = [
            '>',
            '<',
            '\\1',
            '',
        ];

        // حماية أكواد الـ CSS و الـ JS من التلف أثناء الضغط
        $html = preg_replace_callback('/<(script|style|textarea|pre)[^>]*>.*?<\/\1>/isu', function ($matches) {
            return $matches[0];
        }, $html);

        $minified = preg_replace($search, $replace, $html);

        // لا تُهمل نتيجة null: إن فشل التصغير لأي سبب أعِد الأصل بلا تغيير.
        return $minified ?? $html;
    }
}
