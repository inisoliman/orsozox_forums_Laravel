<?php

namespace App\Services;

use App\Models\Thread;
use App\Helpers\SeoHelper;
use Illuminate\Support\Str;

class ThreadSeoService
{
    /**
     * Generate dynamic SEO package for a given thread.
     */
    public function generate(Thread $thread): array
    {
        // Ensure firstPost relation is loaded if not already (it should be, but just in case)
        $firstPost = $thread->firstPost;

        // 1. Title Setup
        $title = mb_substr(strip_tags($thread->title), 0, 70, 'UTF-8');
        $forumName = strip_tags($thread->forum->title ?? '');
        $titleFull = SeoHelper::title($title, $forumName);

        // 2. Clean Description (Sanitized, max 160 chars)
        $rawText = $firstPost ? $firstPost->pagetext : $thread->title;
        // Clean up text using the robust helper, removing nested bbcodes and tags
        $cleanDesc = SeoHelper::description($rawText, 160);
        if (empty($cleanDesc)) {
            $cleanDesc = $title;
        }

        // 3. Fallback Image resolving
        $imageUrl = asset('images/og-default.jpg');
        $hasCustomImage = false;

        if ($firstPost && $firstPost->attachments->count() > 0) {
            $firstImage = $firstPost->attachments->where('is_image', true)->first();
            if ($firstImage) {
                // Return original image path for OG tags to avoid WebP strict limitations on some old crawlers
                $imageUrl = asset('attachments/' . $firstImage->attachmentid . '.' . $firstImage->extension);
                $hasCustomImage = true;
            }
        }

        // 4. Extract Keywords
        $keywords = static::extractKeywords($title);

        // 5. Publish & Modified Times (ISO-8601 for Schema & Graph)
        $publishedTime = clone ($thread->created_date ?? now());
        $modifiedTime = clone ($thread->last_post_date ?? $publishedTime);

        return [
            'title' => $title,
            'description' => $cleanDesc,
            'title_full' => $titleFull,
            'image' => $imageUrl,
            'has_custom_image' => $hasCustomImage,
            'published_time' => $publishedTime->toIso8601String(),
            'modified_time' => $modifiedTime->toIso8601String(),
            'keywords' => $keywords,
            'author_name' => strip_tags($thread->author->username ?? $thread->postusername ?? 'زائر'),
            'forum_name' => $forumName,
            'url' => $thread->url,
            'views' => $thread->views,
            'replies' => $thread->replycount,
            'raw_text' => $firstPost ? mb_substr($firstPost->plain_text ?? '', 0, 500, 'UTF-8') : '',
            'is_question' => preg_match('/^(كيف|هل|لماذا|متى|أين|ما|من|ماذا)\s/iu', $title) || mb_strpos($title, '؟') !== false || mb_strpos($title, '?') !== false,
        ];
    }

    /**
     * استخراج كلمات دلالية من العنوان بآلية مبسطة (اختياري لكن مفيد للبحث القديم)
     */
    public static function extractKeywords(string $title): string
    {
        $words = explode(' ', $title);
        $cleanWords = [];

        $stopWords = ['في', 'من', 'على', 'إلى', 'عن', 'مع', 'هل', 'كيف', 'متى', 'لماذا', 'أين', 'ما', 'هذا', 'هذه', 'التي', 'الذي', 'و', 'أو', 'ثم', 'يا', 'لا', 'لم', 'لن', 'بين'];

        foreach ($words as $word) {
            // Remove non-alphanumeric unicode chars
            $word = trim(preg_replace('/[^\p{L}\p{N}]/u', '', $word));
            if (mb_strlen($word, 'UTF-8') > 2 && !in_array($word, $stopWords)) {
                $cleanWords[] = $word;
            }
        }

        return implode(', ', array_slice($cleanWords, 0, 8)); // Max 8 solid keywords
    }
}
