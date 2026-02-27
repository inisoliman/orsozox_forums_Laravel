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
}
