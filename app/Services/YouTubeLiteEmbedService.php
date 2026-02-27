<?php

namespace App\Services;

class YouTubeLiteEmbedService
{
    /**
     * Valid YouTube Video ID pattern (exactly 11 characters)
     */
    private const VIDEO_ID_PATTERN = '/^[a-zA-Z0-9_-]{11}$/';

    /**
     * Detect and transform YouTube links in text to lightweight preview cards.
     * Handles: [ame] BBCode, <a> tags with YouTube, and standalone URLs.
     * Skips content inside <code> and <pre> tags.
     * 
     * @param string $content
     * @return string
     */
    public function transformContent(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        // Step 1: Handle [ame]...[/ame] BBCode tags (legacy vBulletin)
        $content = $this->handleAmeBBCode($content);

        // Step 2: Split by code/pre blocks to avoid touching code
        $parts = preg_split('/(<code[^>]*>.*?<\/code>|<pre[^>]*>.*?<\/pre>)/si', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as &$part) {
            if (!preg_match('/^<(code|pre)/i', $part)) {
                // Step 3: Replace <a> tags that link to YouTube
                $part = $this->replaceYoutubeLinks($part);
                // Step 4: Replace standalone YouTube URLs (not inside any tag attribute)
                $part = $this->replaceStandaloneUrls($part);
            }
        }

        return implode('', $parts);
    }

    /**
     * Handle legacy vBulletin [ame] BBCode tags.
     * Formats: [ame]URL[/ame] or [ame=URL]Text[/ame]
     */
    private function handleAmeBBCode(string $content): string
    {
        // [ame=URL]Text[/ame]
        $content = preg_replace_callback(
            '/\[ame[=]?"?([^"\]\s]+)"?\](.*?)\[\/ame\]/si',
            function ($matches) {
                $url = trim($matches[1]);
                $videoId = $this->extractVideoId($url);
                if ($videoId) {
                    return $this->getLiteEmbedHtml($videoId, $url);
                }
                return $matches[2]; // Return the text content if not a valid YouTube URL
            },
            $content
        );

        // [ame]URL[/ame] (simple form)
        $content = preg_replace_callback(
            '/\[ame\](https?:\/\/[^\s\[]+)\[\/ame\]/si',
            function ($matches) {
                $url = trim($matches[1]);
                $videoId = $this->extractVideoId($url);
                if ($videoId) {
                    return $this->getLiteEmbedHtml($videoId, $url);
                }
                return '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($url) . '</a>';
            },
            $content
        );

        return $content;
    }

    /**
     * Replace <a> tags that contain YouTube URLs.
     * Replaces the ENTIRE <a>...</a> tag with the lite embed.
     */
    private function replaceYoutubeLinks(string $content): string
    {
        return preg_replace_callback(
            '/<a\s[^>]*href=["\']?(https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?[^\s"\']*v=|shorts\/|embed\/)|youtu\.be\/)[^\s"\'<>]+)["\']?[^>]*>.*?<\/a>/si',
            function ($matches) {
                $url = $matches[1];
                $videoId = $this->extractVideoId($url);
                if ($videoId) {
                    return $this->getLiteEmbedHtml($videoId, $url);
                }
                return $matches[0]; // Return original if not valid
            },
            $content
        );
    }

    /**
     * Replace standalone YouTube URLs that are NOT inside HTML tag attributes.
     * Only matches URLs that are not preceded by href=" or src="
     */
    private function replaceStandaloneUrls(string $content): string
    {
        return preg_replace_callback(
            '/(?<!["\'=>\/])(?:^|(?<=\s|>|\n))(https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?[^\s<]+|shorts\/[a-zA-Z0-9_-]{11})|youtu\.be\/[a-zA-Z0-9_-]{11})[^\s<]*)/mi',
            function ($matches) {
                $url = trim($matches[1]);
                $videoId = $this->extractVideoId($url);
                if ($videoId) {
                    return $this->getLiteEmbedHtml($videoId, $url);
                }
                return $matches[0];
            },
            $content
        );
    }

    /**
     * Extract a valid Video ID from a YouTube URL.
     * 
     * @param string $url
     * @return string|null
     */
    public function extractVideoId(string $url): ?string
    {
        // Clean URL - remove trailing HTML entities or tags
        $url = preg_replace('/<.*$/', '', $url);
        $url = html_entity_decode($url);

        // youtube.com/watch?v=ID
        if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }
        // youtu.be/ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }
        // youtube.com/shorts/ID or youtube.com/embed/ID
        if (preg_match('/youtube\.com\/(?:shorts|embed)\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Generate the lightweight HTML structure for the preview card.
     */
    private function getLiteEmbedHtml(string $videoId, string $originalUrl): string
    {
        $thumbnailUrl = "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
        $safeUrl = htmlspecialchars($originalUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div class="yt-lite" data-id="{$videoId}">
    <div class="yt-thumbnail">
        <img src="{$thumbnailUrl}" alt="Video preview" loading="lazy">
        <div class="yt-play-button"></div>
    </div>
    <div class="yt-footer">
        <a href="{$safeUrl}" target="_blank" rel="noopener noreferrer">
            مشاهدة على يوتيوب <i class="fab fa-youtube ms-1"></i>
        </a>
    </div>
</div>
HTML;
    }
}
