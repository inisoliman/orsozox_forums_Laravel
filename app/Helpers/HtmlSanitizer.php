<?php

namespace App\Helpers;

class HtmlSanitizer
{
    /**
     * Sanitize HTML content to prevent XSS while allowing safe tags.
     * Note: In a large production project, consider installing mews/purifier.
     * This provides a lightweight fallback for basic XSS vectors.
     *
     * @param string $html
     * @return string
     */
    public static function clean(string $html): string
    {
        // 0. حماية مقاطع يوتيوب قبل حذف الـ iframe:
        //    المحرر يحفظ فيديو يوتيوب كـ <iframe> (أو <oembed>/<figure class="media">)،
        //    ولو حذفناه مباشرة لاختفى الفيديو عند الحفظ. نحوّله إلى علامة آمنة
        //    يحوّلها YouTubeLiteEmbedService لاحقاً إلى بطاقة تشغيل جميلة.
        $html = self::preserveYoutubeEmbeds($html);

        // 1. Remove dangerous tags and their contents
        $html = preg_replace('@<(script|style|iframe|object|embed|applet|form|meta|link|svg)[^>]*?>.*?</\1>@si', '', $html);
        $html = preg_replace('@<(script|style|iframe|object|embed|applet|form|meta|link|svg)[^>]*?>@si', '', $html);

        // 2. Remove dangerous javascript: URIs
        $html = preg_replace('/(href|src|action)\s*=\s*(["\'])\s*(javascript|vbscript|data):.*?\2/si', '$1="#"', $html);

        // 3. Remove inline event handlers (onload, onerror, onclick, etc.)
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/si', '', $html);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*[^\s>]+/si', '', $html);

        return trim($html);
    }

    /**
     * تحويل تضمينات يوتيوب الخطرة إلى علامة آمنة قبل الحذف.
     *
     * يعالج الأشكال التي يُنتجها المحرر:
     *  - <iframe src="https://www.youtube.com/embed/ID" ...></iframe>
     *  - <oembed url="https://www.youtube.com/watch?v=ID"></oembed>
     *  - <figure class="media"> ... <iframe ...> ... </figure>
     *
     * الناتج علامة ثابتة: <div class="yt-embed" data-youtube-id="ID"></div>
     * يعالجها YouTubeLiteEmbedService عند العرض.
     */
    private static function preserveYoutubeEmbeds(string $html): string
    {
        if ($html === '' || (stripos($html, 'youtu') === false && stripos($html, 'youtube') === false)) {
            return $html;
        }

        // (أ) <iframe src="...youtube.com/embed/ID..."></iframe>
        $html = preg_replace_callback(
            '#<iframe\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\'][^>]*>\s*</iframe>#si',
            function ($m) {
                $id = self::extractYoutubeId($m[1]);
                return $id !== null ? self::youtubeMarker($id) : $m[0];
            },
            $html
        );

        // (ب) <iframe ... /> بصيغة الإغلاق الذاتي
        $html = preg_replace_callback(
            '#<iframe\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\'][^>]*/?>#si',
            function ($m) {
                $id = self::extractYoutubeId($m[1]);
                return $id !== null ? self::youtubeMarker($id) : $m[0];
            },
            $html
        );

        // (ج) <oembed url="...youtube..."></oembed> (يُنتجه CKEditor mediaEmbed)
        $html = preg_replace_callback(
            '#<oembed\b[^>]*\burl\s*=\s*["\']([^"\']+)["\'][^>]*>\s*</oembed>#si',
            function ($m) {
                $id = self::extractYoutubeId($m[1]);
                return $id !== null ? self::youtubeMarker($id) : '';
            },
            $html
        );

        // (د) <oembed url="..." />
        $html = preg_replace_callback(
            '#<oembed\b[^>]*\burl\s*=\s*["\']([^"\']+)["\'][^>]*/?>#si',
            function ($m) {
                $id = self::extractYoutubeId($m[1]);
                return $id !== null ? self::youtubeMarker($id) : '';
            },
            $html
        );

        return $html;
    }

    /**
     * استخراج معرّف فيديو يوتيوب من رابط (يعيد null إن لم يكن يوتيوب).
     */
    private static function extractYoutubeId(string $url): ?string
    {
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

        if (preg_match('#youtube\.com/embed/([a-zA-Z0-9_-]{11})#i', $url, $m)) {
            return $m[1];
        }
        if (preg_match('#youtube\.com/shorts/([a-zA-Z0-9_-]{11})#i', $url, $m)) {
            return $m[1];
        }
        if (preg_match('#[?&]v=([a-zA-Z0-9_-]{11})#i', $url, $m)) {
            return $m[1];
        }
        if (preg_match('#youtu\.be/([a-zA-Z0-9_-]{11})#i', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * علامة يوتيوب الآمنة التي يعالجها YouTubeLiteEmbedService.
     */
    private static function youtubeMarker(string $videoId): string
    {
        // المعرّف مُتحقَّق منه بـ 11 حرفاً [a-zA-Z0-9_-] فقط، فهو آمن داخل السمة.
        return '<div class="yt-embed" data-youtube-id="' . $videoId . '"></div>';
    }
}

