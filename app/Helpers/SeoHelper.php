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
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $data['url'] ?? '',
            ],
            'headline' => $data['title'] ?? '',
            'image' => $data['image'] ?? asset('images/og-default.jpg'),
            'author' => [
                '@type' => 'Person',
                'name' => $data['author'] ?? '',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'المنتدى'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/og-default.jpg')
                ]
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

    /**
     * إنشاء FAQPage Schema للذكاء الاصطناعي
     */
    public static function schemaFAQPage(string $question, string $answer): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => htmlspecialchars(strip_tags($question), ENT_QUOTES, 'UTF-8'),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => mb_substr(strip_tags($answer), 0, 1000, 'UTF-8')
                    ]
                ]
            ]
        ];

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * إنشاء WebSite & Organization Schema للصفحة الرئيسية
     */
    public static function schemaWebSite(): string
    {
        // 1. WebSite Schema (SearchAction)
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name', 'المنتدى'),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('search') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ];

        // 2. Organization Schema
        $organizationSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name', 'المنتدى'),
            'url' => url('/'),
            'logo' => asset('images/og-default.jpg'),
            'sameAs' => [
                // يمكن استبدالها لاحقاً بروابط من الإعدادات
                'https://x.com/your_account',
                'https://facebook.com/your_page'
            ]
        ];

        // حقن أكثر من مخطط في مصفوفة واحدة
        $schemas = [$websiteSchema, $organizationSchema];

        return '<script type="application/ld+json">' . json_encode($schemas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * إنشاء Author Schema للملفات الشخصية للأعضاء
     */
    public static function schemaPerson($user): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'mainEntityOfPage' => [
                '@type' => 'ProfilePage',
                '@id' => route('user.show', $user->userid)
            ],
            'name' => $user->username,
            'url' => route('user.show', $user->userid),
        ];

        // إضافة الصورة الرمزية إن وجدت مستقبلاً
        // $schema['image'] = url('/path/to/avatar.jpg');

        // إضافة نبذة إن وجدت
        if (!empty($user->usertitle)) {
            $schema['description'] = $user->usertitle;
        }

        // إضافة روابط أخرى للمستخدم (Social) إذا توفرت دالة مخصصة
        if (!empty($user->homepage)) {
            $schema['sameAs'] = [$user->homepage];
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * إنشاء AboutPage Schema لصفحة من نحن
     */
    public static function schemaAboutPage(): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'AboutPage',
            'mainEntity' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'المنتدى'),
                'foundingDate' => '2005', // سنة التأسيس الافتراضية
                'description' => 'مؤسسة دينية تعليمية تهدف لنشر العلم الموثوق وتبادل النقاشات الهادفة.',
                'url' => url('/'),
                'logo' => asset('images/og-default.jpg'),
                'sameAs' => [
                    'https://x.com/your_account',
                    'https://facebook.com/your_page'
                ]
            ]
        ];
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * إنشاء ContactPage Schema لصفحة اتصل بنا
     */
    public static function schemaContactPage(): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'mainEntity' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'المنتدى'),
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'contactType' => 'customer support',
                    'email' => 'admin@yourdomain.com',
                    'availableLanguage' => ['Arabic', 'English']
                ]
            ]
        ];
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * إنشاء WebPage Schema لسياسة التحرير
     */
    public static function schemaEditorialPolicy(): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'سياسة التحرير والمراجعة',
            'description' => 'نتائج المراجعة التحريرية وسياسة النشر الدينية لضمان موثوقية المحتوى E-E-A-T.',
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'المنتدى'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/og-default.jpg')
                ]
            ]
        ];
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }
}
