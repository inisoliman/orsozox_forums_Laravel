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

        // === BBCode Tags ===
        // نستخدم حلقة تكرار لمعالجة التداخل (Nested Tags)
        // مثلاً: [center][color=red]...[/color][/center]

        $maxPasses = 5; // تجنب الحلقات اللانهائية
        $pass = 0;

        do {
            $originalText = $text;

            // Bold — خط عريض
            $text = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $text);

            // Italic — خط مائل
            $text = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $text);

            // Underline — خط سفلي
            $text = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $text);

            // Strike — خط وسطي
            $text = preg_replace('/\[s\](.*?)\[\/s\]/is', '<del>$1</del>', $text);

            // Color — لون النص
            $text = preg_replace('/\[color=(&quot;|&#039;|["\']?)(#[0-9a-fA-F]{3,6}|[a-zA-Z]+)(&quot;|&#039;|["\']?)\](.*?)\[\/color\]/is', '<span style="color:$2">$4</span>', $text);

            // Size — حجم الخط
            $text = preg_replace_callback('/\[size=(&quot;|&#039;|["\']?)([1-7])(&quot;|&#039;|["\']?)\](.*?)\[\/size\]/is', function ($m) {
                $sizes = [1 => '0.7em', 2 => '0.85em', 3 => '1em', 4 => '1.2em', 5 => '1.5em', 6 => '2em', 7 => '2.5em'];
                $size = $sizes[(int) $m[2]] ?? '1em';
                return '<span style="font-size:' . $size . '">' . $m[4] . '</span>';
            }, $text);

            // Font — نوع الخط
            $text = preg_replace('/\[font=(&quot;|&#039;|["\']?)([a-zA-Z\s,\-]+)(&quot;|&#039;|["\']?)\](.*?)\[\/font\]/is', '<span style="font-family:$2">$4</span>', $text);

            // Center — توسيط
            $text = preg_replace('/\[center\](.*?)\[\/center\]/is', '<div class="text-center">$1</div>', $text);

            // Right — محاذاة يمين
            $text = preg_replace('/\[right\](.*?)\[\/right\]/is', '<div class="text-end">$1</div>', $text);

            // Left — محاذاة يسار
            $text = preg_replace('/\[left\](.*?)\[\/left\]/is', '<div class="text-start">$1</div>', $text);

            // URL — روابط
            // 1a. [url=&quot;http://example.com&quot;]Text[/url] (HTML-encoded double quotes)
            $text = preg_replace('/\[url=&quot;(https?:\/\/.*?)&quot;\](.*?)\[\/url\]/is', '<a href="$1" target="_blank" rel="noopener noreferrer" class="bb-link">$2</a>', $text);
            // 1b. [url=&#039;http://example.com&#039;]Text[/url] (HTML-encoded single quotes)
            $text = preg_replace('/\[url=&#039;(https?:\/\/.*?)&#039;\](.*?)\[\/url\]/is', '<a href="$1" target="_blank" rel="noopener noreferrer" class="bb-link">$2</a>', $text);
            // 1c. [url="http://example.com"]Text[/url] (Regular quotes - unlikely after htmlspecialchars)
            $text = preg_replace('/\[url=["\'](https?:\/\/.*?)["\']\](.*?)\[\/url\]/is', '<a href="$1" target="_blank" rel="noopener noreferrer" class="bb-link">$2</a>', $text);
            // 1d. [url=http://example.com]Text[/url] (No quotes at all)
            $text = preg_replace('/\[url=(https?:\/\/[^\]\s]+)\](.*?)\[\/url\]/is', '<a href="$1" target="_blank" rel="noopener noreferrer" class="bb-link">$2</a>', $text);
            // 2. [url]http://example.com[/url] (Simple)
            $text = preg_replace('/\[url\](https?:\/\/[^\[]+)\[\/url\]/is', '<a href="$1" target="_blank" rel="noopener noreferrer" class="bb-link">$1</a>', $text);

            // Email — بريد إلكتروني
            $text = preg_replace('/\[email\]([^\[]+)\[\/email\]/is', '<a href="mailto:$1">$1</a>', $text);
            $text = preg_replace('/\[email=(&quot;|&#039;|["\']?)([^\]]+)(&quot;|&#039;|["\']?)\](.*?)\[\/email\]/is', '<a href="mailto:$2">$4</a>', $text);

            // Image — صور
            $text = preg_replace('/\[img\](https?:\/\/[^\[]+)\[\/img\]/is', '<img src="$1" class="img-fluid bb-img" loading="lazy" alt="صورة">', $text);

            // Quote — اقتباس
            $text = preg_replace(
                '/\[quote=(&quot;|&#039;|["\']?)(.*?)(&quot;|&#039;|["\']?)\](.*?)\[\/quote\]/is',
                '<blockquote class="bb-quote"><div class="bb-quote-author"><i class="bi bi-chat-quote-fill"></i> اقتباس من: $2</div><div class="bb-quote-content">$4</div></blockquote>',
                $text
            );
            $text = preg_replace(
                '/\[quote\](.*?)\[\/quote\]/is',
                '<blockquote class="bb-quote"><div class="bb-quote-content">$1</div></blockquote>',
                $text
            );

            // Code — كود
            $text = preg_replace('/\[code\](.*?)\[\/code\]/is', '<pre class="bb-code"><code>$1</code></pre>', $text);

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
        // يجب أن نكون حذرين لكي لا نفسد الروابط التي داخل <a href="...">
        $text = preg_replace(
            '/(?<!href="|href=\'|src="|src=\')(?<!>)(https?:\/\/[^\s<\[]+)/i',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="bb-link">$1</a>',
            $text
        );

        return $text;
    }

    /**
     * تحويل BBCode إلى نص عادي (للـ SEO والمقتطفات)
     */
    public static function toPlainText(string $text): string
    {
        // إزالة جميع أكواد BBCode
        $text = preg_replace('/\[\/?\w+(?:=[^\]]+)?\]/i', '', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));
        return $text;
    }
}
