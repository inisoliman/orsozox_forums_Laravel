<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrafficClassificationService
{
    public function __construct(private readonly ClientIpResolver $clientIpResolver)
    {
    }

    /** @var array<string, string[]> */
    private array $verifiedSearchEngines = [
        'google' => ['Googlebot', 'Google-InspectionTool', 'GoogleOther', 'Googlebot-Image'],
        'bing' => ['Bingbot', 'BingPreview', 'msnbot'],
    ];

    /** @var string[] */
    private array $aiCrawlers = [
        'GPTBot', 'ClaudeBot', 'CCBot', 'PerplexityBot', 'Bytespider', 'Google-Extended',
        'anthropic-ai', 'Claude-Web', 'cohere-ai', 'Applebot-Extended',
    ];

    /** @var string[] */
    private array $aggressiveScrapers = [
        'AhrefsBot', 'SemrushBot', 'MJ12bot', 'DotBot', 'PetalBot', 'BLEXBot',
        'DataForSeoBot', 'serpstatbot', 'MegaIndex', 'SeekportBot', 'Amazonbot',
    ];

    /** @var string[] */
    private array $socialCrawlers = [
        'facebookexternalhit', 'Twitterbot', 'LinkedInBot', 'WhatsApp', 'TelegramBot', 'Slackbot',
    ];

    /**
     * @return array{class:string, engine:?string, verified:bool, score:int, reason:string}
     */
    public function classify(Request $request): array
    {
        $ua = $request->userAgent() ?? '';

        foreach ($this->verifiedSearchEngines as $engine => $needles) {
            foreach ($needles as $needle) {
                if (stripos($ua, $needle) !== false) {
                    $verified = $this->verifySearchEngineIp($this->clientIpResolver->resolve($request), $engine);

                    return [
                        'class' => $verified ? 'verified_search_engine' : 'fake_search_engine',
                        'engine' => $engine,
                        'verified' => $verified,
                        'score' => $verified ? 0 : 65,
                        'reason' => $verified ? 'verified_reverse_dns' : 'failed_reverse_dns',
                    ];
                }
            }
        }

        foreach ($this->socialCrawlers as $needle) {
            if (stripos($ua, $needle) !== false) {
                return ['class' => 'social_crawler', 'engine' => null, 'verified' => false, 'score' => 5, 'reason' => $needle];
            }
        }

        foreach ($this->aiCrawlers as $needle) {
            if (stripos($ua, $needle) !== false) {
                return ['class' => 'ai_crawler', 'engine' => null, 'verified' => false, 'score' => 80, 'reason' => $needle];
            }
        }

        foreach ($this->aggressiveScrapers as $needle) {
            if (stripos($ua, $needle) !== false) {
                return ['class' => 'aggressive_scraper', 'engine' => null, 'verified' => false, 'score' => 90, 'reason' => $needle];
            }
        }

        if (preg_match('/bot|crawler|spider|scrapy|python-requests|curl|wget|headless/i', $ua)) {
            return ['class' => 'suspicious_crawler', 'engine' => null, 'verified' => false, 'score' => 45, 'reason' => 'generic_automation_signature'];
        }

        return ['class' => 'human_or_unknown', 'engine' => null, 'verified' => false, 'score' => 0, 'reason' => 'default'];
    }

    private function verifySearchEngineIp(string $ip, string $engine): bool
    {
        if ($ip === '') {
            return false;
        }

        $cacheKey = 'verified_bot_dns:' . $engine . ':' . $ip;
        $ttl = (int) config('security.firewall.verified_bot_dns_cache_ttl', 86400);

        return Cache::remember($cacheKey, $ttl, function () use ($ip, $engine) {
            $host = @gethostbyaddr($ip);
            if (!$host || $host === $ip) {
                return false;
            }

            $host = strtolower($host);
            $validSuffix = match ($engine) {
                'google' => str_ends_with($host, '.googlebot.com') || str_ends_with($host, '.google.com'),
                'bing' => str_ends_with($host, '.search.msn.com'),
                default => false,
            };

            if (!$validSuffix) {
                return false;
            }

            $forward = @gethostbynamel($host) ?: [];

            return in_array($ip, $forward, true);
        });
    }
}
