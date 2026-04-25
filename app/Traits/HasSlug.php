<?php

namespace App\Traits;

/**
 * Shared slug-generation logic for Arabic content.
 */
trait HasSlug
{
    /**
     * Create a URL-safe slug from the given Arabic or multilingual text.
     *
     * @param string|null $text
     * @param string $fallback
     * @return string
     */
    public function createSlug(?string $text, string $fallback = 'item'): string
    {
        if (empty($text)) {
            return $fallback;
        }

        $text = trim($text);
        $text = preg_replace('/\s+/u', '-', $text);
        $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');

        return $text ?: $fallback;
    }
}