<?php

namespace App\Services;

class PlainUrlLinkifierService
{
    private const HTML_MARKER = '<!-- HTML -->';

    /**
     * @return array{content:string,replacements:int,links:array<int,array{from:string,to:string}>}
     */
    public function linkifyContent(string $content): array
    {
        if (!$this->mayContainPlainUrl($content)) {
            return [
                'content' => $content,
                'replacements' => 0,
                'links' => [],
            ];
        }

        if (str_starts_with($content, self::HTML_MARKER)) {
            $html = substr($content, strlen(self::HTML_MARKER));
            $result = $this->linkifyHtml($html);
            $result['content'] = self::HTML_MARKER . $result['content'];

            return $result;
        }

        return $this->linkifyBbCode($content);
    }

    private function mayContainPlainUrl(string $content): bool
    {
        return preg_match('~https?://|www\.~i', $content) === 1;
    }

    /**
     * @return array{content:string,replacements:int,links:array<int,array{from:string,to:string}>}
     */
    private function linkifyBbCode(string $content): array
    {
        return $this->linkifyUnprotectedText($content, $this->bbCodeProtectedPatterns(), function (string $url): string {
            $href = $this->hrefFor($url);

            return '[url=' . $href . ']' . $url . '[/url]';
        });
    }

    /**
     * @return array{content:string,replacements:int,links:array<int,array{from:string,to:string}>}
     */
    private function linkifyHtml(string $content): array
    {
        return $this->linkifyUnprotectedText($content, $this->htmlProtectedPatterns(), function (string $url): string {
            $href = htmlspecialchars($this->hrefFor($url), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_NOQUOTES, 'UTF-8');

            return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
        });
    }

    /**
     * @param  array<int, string>  $protectedPatterns
     * @return array{content:string,replacements:int,links:array<int,array{from:string,to:string}>}
     */
    private function linkifyUnprotectedText(string $content, array $protectedPatterns, callable $replacementFactory): array
    {
        [$maskedContent, $protectedSegments] = $this->maskProtectedSegments($content, $protectedPatterns);
        $links = [];

        $linkedContent = preg_replace_callback(
            '~(?<![\w@/])(?P<url>https?://[^\s<>"\'\[\]]+|www\.[^\s<>"\'\[\]]+)~iu',
            function (array $match) use (&$links, $replacementFactory): string {
                [$url, $suffix] = $this->splitTrailingPunctuation($match['url']);

                if ($url === '' || !$this->isLinkableUrl($url)) {
                    return $match['url'];
                }

                $replacement = $replacementFactory($url);
                $links[] = [
                    'from' => $url,
                    'to' => $replacement,
                ];

                return $replacement . $suffix;
            },
            $maskedContent
        );

        $content = $this->restoreProtectedSegments($linkedContent ?? $maskedContent, $protectedSegments);

        return [
            'content' => $content,
            'replacements' => count($links),
            'links' => $links,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function bbCodeProtectedPatterns(): array
    {
        return [
            '~\[url(?:=[^\]]*)?\].*?\[/url\]~is',
            '~\[img\].*?\[/img\]~is',
            '~\[youtube\].*?\[/youtube\]~is',
            '~\[ame\].*?\[/ame\]~is',
            '~\[code\].*?\[/code\]~is',
            '~<a\b[^>]*>.*?</a>~is',
            '~<img\b[^>]*>~is',
            '~<[^>]+>~is',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function htmlProtectedPatterns(): array
    {
        return [
            '~<a\b[^>]*>.*?</a>~is',
            '~<img\b[^>]*>~is',
            '~<code\b[^>]*>.*?</code>~is',
            '~<pre\b[^>]*>.*?</pre>~is',
            '~<[^>]+>~is',
        ];
    }

    /**
     * @param  array<int, string>  $patterns
     * @return array{0:string,1:array<string, string>}
     */
    private function maskProtectedSegments(string $content, array $patterns): array
    {
        $segments = [];

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, function (array $match) use (&$segments): string {
                $token = " \x1AURLMASK" . count($segments) . "\x1A ";
                $segments[$token] = $match[0];

                return $token;
            }, $content) ?? $content;
        }

        return [$content, $segments];
    }

    /**
     * @param  array<string, string>  $segments
     */
    private function restoreProtectedSegments(string $content, array $segments): string
    {
        return strtr($content, $segments);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitTrailingPunctuation(string $url): array
    {
        $suffix = '';

        while ($url !== '' && preg_match('/[.,;:!?\x{060C}\x{061B}\x{061F}\)\]\}]+$/u', $url, $match)) {
            $suffix = $match[0] . $suffix;
            $url = substr($url, 0, -strlen($match[0]));
        }

        return [$url, $suffix];
    }

    private function hrefFor(string $url): string
    {
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (str_starts_with(strtolower($decoded), 'www.')) {
            return 'https://' . $decoded;
        }

        return $decoded;
    }

    private function isLinkableUrl(string $url): bool
    {
        return filter_var($this->hrefFor($url), FILTER_VALIDATE_URL) !== false;
    }
}
