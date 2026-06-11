<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FirewallDecisionService
{
    public function __construct(private readonly TrafficClassificationService $classifier)
    {
    }

    /**
     * @return array{action:string, status:int, retry_after:int, class:string, score:int, reason:string}
     */
    public function decide(Request $request): array
    {
        $classification = $this->classifier->classify($request);
        $ip = $request->ip() ?? 'unknown';
        $path = '/' . ltrim($request->path(), '/');
        $identity = sha1($ip . '|' . substr($request->userAgent() ?? '', 0, 160));

        if ($classification['class'] === 'verified_search_engine') {
            $this->countTraffic($classification['class']);
            return $this->allow($classification, 'seo_safe_verified_bot');
        }

        if (Cache::has('firewall_ban:' . $identity)) {
            return $this->deny($classification, 429, (int) config('security.firewall.temporary_ban_ttl', 900), 'temporary_ban_active');
        }

        $minuteKey = 'fw_rate:' . $identity . ':' . now()->format('YmdHi');
        $urlKey = 'fw_url:' . $identity . ':' . sha1($path) . ':' . now()->format('YmdHi');
        $minuteHits = $this->increment($minuteKey, 70);
        $sameUrlHits = $this->increment($urlKey, 70);

        $score = $classification['score'];
        $score += $this->isExpensivePath($path) ? 20 : 0;
        $score += $minuteHits > 60 ? 35 : ($minuteHits > 30 ? 15 : 0);
        $score += $sameUrlHits > 25 ? 35 : ($sameUrlHits > 12 ? 15 : 0);
        $score += $this->hasSuspiciousQuery($request) ? 20 : 0;

        $this->countTraffic($classification['class']);
        $this->updateSurvivalMode();

        if ($score >= 100) {
            Cache::put('firewall_ban:' . $identity, true, (int) config('security.firewall.temporary_ban_ttl', 900));
            $this->logSuspicious($request, $classification, $score, 'temporary_ban_created');
            return $this->deny($classification, 429, (int) config('security.firewall.temporary_ban_ttl', 900), 'temporary_ban_created', $score);
        }

        if ($score >= 75) {
            $this->logSuspicious($request, $classification, $score, 'cooldown');
            return $this->deny($classification, 429, (int) config('security.firewall.cooldown_ttl', 120), 'cooldown', $score);
        }

        return $this->allow($classification, 'allowed', $score);
    }

    public function survivalModeActive(): bool
    {
        return Cache::has('survival_mode_active');
    }

    private function increment(string $key, int $ttl): int
    {
        $value = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $value, $ttl);
        return $value;
    }

    private function updateSurvivalMode(): void
    {
        $key = 'site_requests:' . now()->format('YmdHi');
        $hits = $this->increment($key, 70);
        $threshold = (int) config('security.firewall.survival_mode_threshold_per_minute', 240);

        if ($hits > $threshold) {
            Cache::put('survival_mode_active', true, (int) config('security.firewall.survival_mode_ttl', 300));
        }
    }

    private function isExpensivePath(string $path): bool
    {
        foreach (['/search', '/online-users', '/editor/upload', '/editor/upload-url', '/api/', '/livewire/'] as $fragment) {
            if (str_starts_with($path, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function hasSuspiciousQuery(Request $request): bool
    {
        $query = urldecode($request->getQueryString() ?? '');
        return (bool) preg_match('/(union\s+select|base64_|\.env|wp-login|xmlrpc|\.git|select.+from)/i', $query . ' ' . $request->path());
    }

    /** @param array{class:string, engine:?string, verified:bool, score:int, reason:string} $classification */
    private function logSuspicious(Request $request, array $classification, int $score, string $action): void
    {
        Log::warning('firewall_event', [
            'action' => $action,
            'score' => $score,
            'class' => $classification['class'],
            'reason' => $classification['reason'],
            'ip' => $request->ip(),
            'country' => $request->headers->get('CF-IPCountry', 'unknown'),
            'url' => $request->fullUrl(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 180),
        ]);
    }

    private function countTraffic(string $class): void
    {
        $key = 'traffic_class:' . now()->format('YmdH') . ':' . $class;
        Cache::put($key, (int) Cache::get($key, 0) + 1, 7200);
    }

    /** @param array{class:string, engine:?string, verified:bool, score:int, reason:string} $classification */
    private function allow(array $classification, string $reason, int $score = 0): array
    {
        return ['action' => 'allow', 'status' => 200, 'retry_after' => 0, 'class' => $classification['class'], 'score' => $score, 'reason' => $reason];
    }

    /** @param array{class:string, engine:?string, verified:bool, score:int, reason:string} $classification */
    private function deny(array $classification, int $status, int $retryAfter, string $reason, ?int $score = null): array
    {
        return ['action' => 'deny', 'status' => $status, 'retry_after' => $retryAfter, 'class' => $classification['class'], 'score' => $score ?? $classification['score'], 'reason' => $reason];
    }
}