<?php

namespace App\Services;

class RedirectorLinkCleanerService
{
    /**
     * @return array{content:string,replacements:int,links:array<int,array{from:string,to:string}>}
     */
    public function cleanContent(string $content): array
    {
        if ($content === '') {
            return [
                'content' => $content,
                'replacements' => 0,
                'links' => [],
            ];
        }

        if (stripos($content, 'redirector.php') === false) {
            $repaired = $this->repairConcatenatedUrls($content);

            return [
                'content' => $repaired['content'],
                'replacements' => count($repaired['links']),
                'links' => $repaired['links'],
            ];
        }

        $links = [];
        $cleaned = preg_replace_callback(
            '~(?P<url>(?:(?:https?:)?//[a-z0-9.-]+(?::\d+)?/|[a-z0-9.-]+\.[a-z]{2,}(?::\d+)?/|/)?(?:[a-z0-9._%-]+/)*redirector\.php\?[^\s<>"\'\]\)]+)~i',
            function (array $match) use (&$links): string {
                $redirectorUrl = $match['url'];
                $targetUrl = $this->extractTargetUrl($redirectorUrl);

                if ($targetUrl === null || $targetUrl === $redirectorUrl) {
                    return $redirectorUrl;
                }

                $links[] = [
                    'from' => $redirectorUrl,
                    'to' => $targetUrl,
                ];

                return $targetUrl;
            },
            $content
        );

        $repaired = $this->repairConcatenatedUrls($cleaned ?? $content);
        $links = array_merge($links, $repaired['links']);

        return [
            'content' => $repaired['content'],
            'replacements' => count($links),
            'links' => $links,
        ];
    }

    public function extractTargetUrl(string $redirectorUrl): ?string
    {
        $redirectorUrl = $this->normalizeRedirectorUrl($redirectorUrl);
        $parts = parse_url($redirectorUrl);

        if (!is_array($parts)) {
            return null;
        }

        if (!$this->isRedirectorUrl($parts)) {
            return null;
        }

        $query = $parts['query'] ?? '';
        if ($query === '' || !preg_match('/(?:^|&)url=([^&]*)/i', $query, $match)) {
            return null;
        }

        $targetUrl = $this->decodeUrlValue($match[1]);
        if (!$this->isValidExternalUrl($targetUrl)) {
            return null;
        }

        $targetParts = parse_url($targetUrl);
        if (is_array($targetParts) && $this->isRedirectorUrl($targetParts)) {
            return null;
        }

        return $targetUrl;
    }

    /**
     * @return array{content:string,links:array<int,array{from:string,to:string}>}
     */
    private function repairConcatenatedUrls(string $content): array
    {
        $links = [];
        $repairedContent = preg_replace_callback(
            '~(?P<url>https?://[a-z0-9.-]+(?::\d+)?/[^\s<>"\'\]\)\?]*?(?P<target>https?://[^\s<>"\'\]\)]+))~i',
            function (array $match) use (&$links): string {
                $targetUrl = $this->decodeUrlValue($match['target']);

                if (!$this->isValidExternalUrl($targetUrl)) {
                    return $match['url'];
                }

                $links[] = [
                    'from' => $match['url'],
                    'to' => $targetUrl,
                ];

                return $targetUrl;
            },
            $content
        );

        return [
            'content' => $repairedContent ?? $content,
            'links' => $links,
        ];
    }

    private function normalizeRedirectorUrl(string $redirectorUrl): string
    {
        $redirectorUrl = html_entity_decode($redirectorUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('~^[a-z0-9.-]+\.[a-z]{2,}/~i', $redirectorUrl)) {
            return 'https://' . $redirectorUrl;
        }

        return $redirectorUrl;
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private function isRedirectorUrl(array $parts): bool
    {
        $path = strtolower((string) ($parts['path'] ?? ''));
        return str_ends_with($path, '/redirector.php') || $path === 'redirector.php';
    }

    private function decodeUrlValue(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        for ($i = 0; $i < 3; $i++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }

            $decoded = $next;
        }

        return trim($decoded);
    }

    private function isValidExternalUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}
