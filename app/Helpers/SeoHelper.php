<?php

namespace App\Helpers;

class SeoHelper
{
    /**
     * إنشاء Meta Title
     */
    public static function title(string $title, ?string $section = null): string
    {
        $siteName = config('app.name', 'المنتدى');
        if ($section) {
            return $title . ' - ' . $section . ' | ' . $siteName;
        }
        return $title . ' | ' . $siteName;
    }

    /**
     * إنشاء Meta Description
     */
    public static function description(string $text, int $length = 160): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\[\/?\w+(?:=[^\]]+)?\]/i', '', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length, 'UTF-8') . '...';
        }
        return $text;
    }

    /**
     * إنشاء Open Graph Tags
     */
    public static function openGraph(array $data): string
    {
        $tags = '';
        $defaults = [
            'og:type' => 'article',
            'og:locale' => 'ar_AR',
            'og:site_name' => config('app.name', 'المنتدى'),
        ];

        $data = array_merge($defaults, $data);

        foreach ($data as $property => $content) {
            if (!empty($content)) {
                $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
                $tags .= '<meta property="' . $property . '" content="' . $content . '">' . "\n";
            }
        }

        return $tags;
    }

    /**
     * إنشاء Schema.org Article JSON-LD
     */
    public static function schemaArticle(array $data): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'DiscussionForumPosting',
            'headline' => $data['title'] ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $data['author'] ?? '',
            ],
            'datePublished' => $data['datePublished'] ?? '',
            'dateModified' => $data['dateModified'] ?? $data['datePublished'] ?? '',
            'interactionStatistic' => [
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/ViewAction',
                    'userInteractionCount' => $data['views'] ?? 0,
                ],
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/CommentAction',
                    'userInteractionCount' => $data['replies'] ?? 0,
                ],
            ],
            'text' => mb_substr($data['text'] ?? '', 0, 500, 'UTF-8'),
            'url' => $data['url'] ?? '',
        ];

        if (!empty($data['forum'])) {
            $schema['isPartOf'] = [
                '@type' => 'DiscussionForum',
                'name' => $data['forum'],
            ];
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * إنشاء BreadcrumbList Schema
     */
    public static function schemaBreadcrumb(array $items): string
    {
        $listItems = [];
        foreach ($items as $i => $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'] ?? null,
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
