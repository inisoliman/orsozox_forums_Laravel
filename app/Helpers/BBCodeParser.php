<?php

namespace App\Helpers;

class BBCodeParser
{
    /**
     * تحويل BBCode الخاص بـ vBulletin إلى HTML آمن
     */
    public static function parse(string $text): string
    {
        if (empty($text))
            return '';

        // تنظيف XSS أولاً
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // تحويل الأسطر الجديدة
        $text = nl2br($text);

        // === Unbounded BBCode Tags (Styles and Formatting) ===
        // Bold
        $text = str_ireplace('[b]', '<strong>', $text);
        $text = str_ireplace('[/b]', '</strong>', $text);

        // Italic
        $text = str_ireplace('[i]', '<em>', $text);
        $text = str_ireplace('[/i]', '</em>', $text);

        // Underline
        $text = str_ireplace('[u]', '<u>', $text);
        $text = str_ireplace('[/u]', '</u>', $text);

        // Strike
        $text = str_ireplace('[s]', '<del>', $text);
        $text = str_ireplace('[/s]', '</del>', $text);

        // Color
        $text = preg_replace('/\[color=(?:&quot;|&#039;|["\'])?([^\]]+?)(?:&quot;|&#039;|["\'])?\]/i', '<span style="color:$1">', $text);
        $text = str_ireplace('[/color]', '</span>', $text);

        // Size
        $text = preg_replace_callback('/\[size=(?:&quot;|&#039;|["\'])?([0-9a-zA-Z]+)(?:&quot;|&#039;|["\'])?\]/i', function ($m) {
            // استخدام rem بدلاً من em لتجنب تضاعف الحجم عند التداخل
            $sizes = [1 => '0.8rem', 2 => '0.9rem', 3 => '1rem', 4 => '1.2rem', 5 => '1.5rem', 6 => '1.8rem', 7 => '2.2rem'];
            $sizeVal = trim($m[1]);

            if (is_numeric($sizeVal)) {
                $num = (int) $sizeVal;
                if ($num > 7)
                    $num = 7; // الحد الأقصى
                $size = $sizes[$num] ?? '1rem';
            } else {
                $size = htmlspecialchars($sizeVal);
                // حماية من الأحجام الضخمة المكتوبة يدوياً
                if (preg_match('/^(\d+)(px|pt|em|rem|%)$/i', $size, $matches)) {
                    $val = (float) $matches[1];
                    if (strtolower($matches[2]) === 'px' && $val > 35)
                        $size = '35px';
                    if (strtolower($matches[2]) === 'em' && $val > 2.5)
                        $size = '2.5rem';
                    if (strtolower($matches[2]) === 'rem' && $val > 2.5)
                        $size = '2.5rem';
                }
            }
            return '<span style="font-size:' . $size . ' !important;">';
        }, $text);
        $text = str_ireplace('[/size]', '</span>', $text);

        // Font
        $text = preg_replace('/\[font=(?:&quot;|&#039;|["\'])?([^\]]+?)(?:&quot;|&#039;|["\'])?\]/i', '<span style="font-family:\'$1\'">', $text);
        $text = str_ireplace('[/font]', '</span>', $text);

        // Align
        $text = preg_replace('/\[align=(?:&quot;|&#039;|["\'])?([^\]]+?)(?:&quot;|&#039;|["\'])?\]/i', '<div style="text-align:$1">', $text);
        $text = str_ireplace('[/align]', '</div>', $text);

        // Center/Right/Left Fix
        $text = str_ireplace('[center]', '<div class="text-center">', $text);
        $text = str_ireplace('[/center]', '</div>', $text);
        $text = str_ireplace('[right]', '<div class="text-end">', $text);
        $text = str_ireplace('[/right]', '</div>', $text);
        $text = str_ireplace('[left]', '<div class="text-start">', $text);
        $text = str_ireplace('[/left]', '</div>', $text);

        // Code — كود
        $text = preg_replace('/\[code\](.*?)\[\/code\]/is', '<pre class="bb-code" dir="ltr"><code>$1</code></pre>', $text);

        // HR — خط فاصل
        $text = preg_replace('/\[hr\]/i', '<hr class="bb-hr">', $text);

        // === Bounded BBCode Tags (Structured Data) ===
        // نستخدم حلقة تكرار لمعالجة التداخل (Nested Tags) مثل [quote]
        $maxPasses = 5; // تجنب الحلقات اللانهائية
        $pass = 0;

        do {
            $originalText = $text;

            // URL — روابط (Modified to display as stylish buttons)
            $text = preg_replace(
                '/\[url=(?:&quot;|&#039;|["\'])?(.*?)(?:&quot;|&#039;|["\'])?\](.*?)\[\/url\]/is',
                '<div class="my-2"><a href="$1" target="_blank" rel="noopener noreferrer" class="bb-link-styled"><i class="fas fa-external-link-alt me-2"></i>$2</a></div>',
                $text
            );
            $text = preg_replace(
                '/\[url\](.*?)\[\/url\]/is',
                '<div class="my-2"><a href="$1" target="_blank" rel="noopener noreferrer" class="bb-link-styled"><i class="fas fa-external-link-alt me-2"></i>اضغط هنا للذهاب إلى الرابط</a></div>',
                $text
            );

            // Email — بريد إلكتروني
            $text = preg_replace('/\[email\]([^\[]+)\[\/email\]/is', '<a href="mailto:$1" class="bb-link">$1</a>', $text);
            $text = preg_replace('/\[email=(?:&quot;|&#039;|["\'])?([^\]]+?)(?:&quot;|&#039;|["\'])?\](.*?)\[\/email\]/is', '<a href="mailto:$1" class="bb-link">$2</a>', $text);

            // Image — صور
            $text = preg_replace('/\[img\](https?:\/\/[^\[]+)\[\/img\]/is', '<img src="$1" class="img-fluid bb-img" loading="lazy" alt="صورة">', $text);

            // Quote — اقتباس
            $text = preg_replace(
                '/\[quote=(?:&quot;|&#039;|["\'])?(.*?)(?:&quot;|&#039;|["\'])?\](.*?)\[\/quote\]/is',
                '<blockquote class="bb-quote"><div class="bb-quote-author"><i class="bi bi-chat-quote-fill"></i> اقتباس من: $1</div><div class="bb-quote-content">$2</div></blockquote>',
                $text
            );
            $text = preg_replace(
                '/\[quote\](.*?)\[\/quote\]/is',
                '<blockquote class="bb-quote"><div class="bb-quote-content">$1</div></blockquote>',
                $text
            );

            // Indent — مسافة بادئة
            $text = preg_replace('/\[indent\](.*?)\[\/indent\]/is', '<div style="margin-right:2em">$1</div>', $text);

            $pass++;
        } while ($text !== $originalText && $pass < $maxPasses);

        // List — قوائم (تتم مرة واحدة لأنها معقدة في التداخل)
        $text = preg_replace('/\[list\](.*?)\[\/list\]/is', '<ul class="bb-list">$1</ul>', $text);
        $text = preg_replace('/\[list=1\](.*?)\[\/list\]/is', '<ol class="bb-list">$1</ol>', $text);
        $text = preg_replace('/\[\*\](.*?)(?=\[\*\]|\[\/list\]|$)/is', '<li>$1</li>', $text);

        // YouTube — فيديو يوتيوب
        $text = preg_replace(
            '/\[youtube\](?:https?:\/\/(?:www\.)?youtube\.com\/watch\?v=|https?:\/\/youtu\.be\/)([a-zA-Z0-9_\-]+)[^\[]*\[\/youtube\]/is',
            '<div class="ratio ratio-16x9 my-3"><iframe src="https://www.youtube.com/embed/$1" allowfullscreen loading="lazy"></iframe></div>',
            $text
        );

        // Spoiler — محتوى مخفي
        $text = preg_replace(
            '/\[spoiler\](.*?)\[\/spoiler\]/is',
            '<div class="bb-spoiler"><button class="btn btn-sm btn-outline-secondary mb-2" onclick="this.nextElementSibling.classList.toggle(\'d-none\')">عرض المحتوى المخفي</button><div class="d-none">$1</div></div>',
            $text
        );

        // HR — خط فاصل
        $text = preg_replace('/\[hr\]/i', '<hr class="bb-hr">', $text);

        // تحويل روابط عادية إلى روابط قابلة للنقر (خارج التاغات)
        // نستخدم المظهر الجديد كزر أيضاً
        $text = preg_replace(
            '/(?<!href="|href=\'|src="|src=\')(?<!>)(https?:\/\/[^\s<\[]+)/i',
            '<div class="my-2"><a href="$1" target="_blank" rel="noopener noreferrer" class="bb-link-styled"><i class="fas fa-external-link-alt me-2"></i>اضغط هنا للذهاب إلى الرابط</a></div>',
            $text
        );

        // رموز الأيقونات القديمة (كانت صور gif حُذفت) → أزرار تحميل جميلة
        $text = self::convertSmilies($text);

        return $text;
    }

    /**
     * أيقونات التحميل الجميلة — بديل الرموز القديمة :1: ... :10:
     *
     * في vBulletin القديم كانت هذه الرموز تُستبدَل بصور gif من مجلد القالب،
     * وأُزيلت تلك الصور فصارت الرموز تُعرض كنص خام. هنا نحوّلها إلى أزرار
     * تحميل أنيقة مبنية بـ CSS (بلا أي اعتماد على ملفات صور خارجية).
     *
     * @var array<string, array{icon: string, label: string, tone: string}>
     */
    private const DOWNLOAD_ICONS = [
        ':1:'  => ['icon' => 'fa-file-arrow-down', 'label' => 'تحميل',    'tone' => 'blue'],
        ':2:'  => ['icon' => 'fa-camera-retro',    'label' => 'صور',      'tone' => 'violet'],
        ':3:'  => ['icon' => 'fa-music',           'label' => 'صوتيات',   'tone' => 'green'],
        ':4:'  => ['icon' => 'fa-video',           'label' => 'فيديو',    'tone' => 'red'],
        ':5:'  => ['icon' => 'fa-book-open',       'label' => 'مستندات',  'tone' => 'amber'],
        ':6:'  => ['icon' => 'fa-file-zipper',     'label' => 'ملفات مضغوطة', 'tone' => 'slate'],
        ':7:'  => ['icon' => 'fa-mobile-screen',   'label' => 'برامج',    'tone' => 'cyan'],
        ':8:'  => ['icon' => 'fa-palette',         'label' => 'تصاميم',   'tone' => 'pink'],
        ':9:'  => ['icon' => 'fa-gamepad',         'label' => 'ألعاب',    'tone' => 'indigo'],
        ':10:' => ['icon' => 'fa-star',            'label' => 'حصري',     'tone' => 'gold'],
    ];

    /**
     * تحويل رموز الأيقونات القديمة إلى أزرار تحميل جميلة.
     *
     * الحرص: لا نستبدل الرمز إذا كان داخل وسم HTML أو خاصية (مثل href/src)
     * تفادياً لإفساد الروابط أو الترميز. لذلك نمرّ على النص ونتخطى أي
     * مقطع واقع داخل <...>.
     */
    public static function convertSmilies(string $html): string
    {
        if ($html === '' || strpos($html, ':') === false) {
            return $html;
        }

        $pattern = '/(?<![\w:])(:10:'
            . '|:9:|:8:|:7:|:6:|:5:|:4:|:3:|:2:|:1:)'
            . '(?![\w:])/';

        // نحمي كتل الكود/المحرر أولاً حتى لا تُحوَّل الرموز داخلها (تبقى نصاً حرفياً).
        // نستبدلها مؤقتاً بعناصر نائبة ثم نعيدها كما هي في النهاية.
        $vault = [];
        $protected = preg_replace_callback(
            '/<(pre|code|textarea|script)\b[^>]*>.*?<\/\1>/isu',
            function ($m) use (&$vault) {
                $key = "\x00SMILIE_KEEP_" . count($vault) . "\x00";
                $vault[$key] = $m[0];
                return $key;
            },
            $html
        );

        if ($protected === null) {
            // فشل الحماية لأي سبب — نعود للأصل بلا تحويل (أأمن من إفساد المحتوى).
            return $html;
        }

        // نقسّم النص إلى: وسوم HTML (<...>) وبقية النص، ونحوّل في البقية فقط.
        $parts = preg_split('/(<[^>]*>)/u', $protected, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }

        foreach ($parts as $i => $part) {
            // العناصر ذات الفهرس الفردي هي وسوم HTML — نتخطاها كما هي.
            if ($i % 2 === 1) {
                continue;
            }

            $parts[$i] = preg_replace_callback($pattern, function ($m) {
                $code = $m[1];
                $meta = self::DOWNLOAD_ICONS[$code] ?? null;
                if ($meta === null) {
                    return $code;
                }

                return self::renderDownloadButton($meta['icon'], $meta['label'], $meta['tone']);
            }, $part);
        }

        $result = implode('', $parts);

        // نعيد كتل الكود المحمية كما كانت.
        if ($vault !== []) {
            $result = strtr($result, $vault);
        }

        return $result;
    }

    /**
     * بناء زر التحميل كـ HTML أنيق.
     */
    private static function renderDownloadButton(string $icon, string $label, string $tone): string
    {
        return '<span class="bb-download-btn bb-dl-' . $tone . '"'
            . ' role="button" tabindex="0"'
            . ' title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="fas ' . $icon . '"></i>'
            . '<span class="bb-dl-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
            . '<i class="fas fa-download bb-dl-arrow"></i>'
            . '</span>';
    }

    /**
     * تحويل BBCode إلى نص عادي (للـ SEO والمقتطفات)
     */
    public static function toPlainText(string $text): string
    {
        // إزالة جميع أكواد BBCode
        $text = preg_replace('/\[\/?\w+(?:=[^\]]+)?\]/i', '', $text);
        // إزالة رموز الأيقونات القديمة (:1: ... :10:) من النص العادي
        $text = preg_replace('/(?<![\w:]):(?:10|[1-9]):(?![\w:])/', '', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));
        return $text;
    }
}
