<?php

namespace App\Helpers;

class SearchHighlightHelper
{
    /**
     * Create a highlighted excerpt from text around the search keyword.
     *
     * 1. Strips all HTML/BBCode tags from text
     * 2. Finds the keyword position
     * 3. Creates an excerpt centered around the keyword
     * 4. Wraps all keyword occurrences with <mark>
     * 5. Case-insensitive, supports Arabic text
     *
     * @param string $text     Raw text (may contain HTML/BBCode)
     * @param string $query    The search keyword(s)
     * @param int    $maxLength Maximum excerpt length (default: 280)
     * @return string          Safe HTML excerpt with <mark> highlights
     */
    public static function highlight(string $text, string $query, int $maxLength = 280): string
    {
        if (empty($text) || empty($query)) {
            return '';
        }

        // Step 1: Strip HTML and BBCode, normalize whitespace
        $text = strip_tags($text);
        $text = preg_replace('/\[.*?\]/u', '', $text);        // Remove BBCode tags
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);

        if (empty($text)) {
            return '';
        }

        // Step 2: Find keyword position (case-insensitive, Unicode-safe)
        $queryLower = mb_strtolower($query, 'UTF-8');
        $textLower = mb_strtolower($text, 'UTF-8');
        $pos = mb_strpos($textLower, $queryLower, 0, 'UTF-8');

        // Step 3: Create excerpt centered around keyword
        $textLength = mb_strlen($text, 'UTF-8');

        if ($pos !== false) {
            // Center the excerpt around the keyword
            $start = max(0, $pos - (int) floor($maxLength / 3));
            $excerpt = mb_substr($text, $start, $maxLength, 'UTF-8');

            // Add ellipsis if needed
            if ($start > 0) {
                // Find first space to avoid cutting a word
                $firstSpace = mb_strpos($excerpt, ' ', 0, 'UTF-8');
                if ($firstSpace !== false && $firstSpace < 30) {
                    $excerpt = mb_substr($excerpt, $firstSpace + 1, null, 'UTF-8');
                }
                $excerpt = '… ' . $excerpt;
            }
            if ($start + $maxLength < $textLength) {
                // Find last space to avoid cutting a word
                $lastSpace = mb_strrpos($excerpt, ' ', 0, 'UTF-8');
                if ($lastSpace !== false && $lastSpace > mb_strlen($excerpt, 'UTF-8') - 30) {
                    $excerpt = mb_substr($excerpt, 0, $lastSpace, 'UTF-8');
                }
                $excerpt .= ' …';
            }
        } else {
            // Keyword not found — just take the beginning
            $excerpt = mb_substr($text, 0, $maxLength, 'UTF-8');
            if ($textLength > $maxLength) {
                $lastSpace = mb_strrpos($excerpt, ' ', 0, 'UTF-8');
                if ($lastSpace !== false) {
                    $excerpt = mb_substr($excerpt, 0, $lastSpace, 'UTF-8');
                }
                $excerpt .= ' …';
            }
        }

        // Step 4: Escape HTML in excerpt to prevent XSS
        $excerpt = htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8');

        // Step 5: Wrap keywords with <mark> (case-insensitive)
        $queryEscaped = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
        $pattern = '/(' . preg_quote($queryEscaped, '/') . ')/iu';
        $excerpt = preg_replace($pattern, '<mark>$1</mark>', $excerpt);

        return $excerpt;
    }

    /**
     * Highlight keywords in a title string.
     * Same logic but no excerpt truncation.
     *
     * @param string $title  The title text
     * @param string $query  The search keyword(s)
     * @return string        Safe HTML with <mark> highlights
     */
    public static function highlightTitle(string $title, string $query): string
    {
        if (empty($title) || empty($query)) {
            return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        }

        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $queryEscaped = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
        $pattern = '/(' . preg_quote($queryEscaped, '/') . ')/iu';

        return preg_replace($pattern, '<mark>$1</mark>', $title);
    }
}
