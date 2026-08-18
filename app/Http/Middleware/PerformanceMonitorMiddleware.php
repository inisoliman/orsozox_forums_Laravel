<?php

namespace App\Http\Middleware;

use App\Services\ClientIpResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs slow dynamic requests so the heaviest URLs can be identified from storage/logs.
 * Kept intentionally lightweight: no DB writes and no external services.
 */
class PerformanceMonitorMiddleware
{
    public function __construct(private readonly ClientIpResolver $clientIpResolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $thresholdMs = (int) env('PERFORMANCE_SLOW_REQUEST_MS', 1200);

        if ($durationMs >= $thresholdMs && !$this->isStaticAsset($request->path())) {
            Log::channel(config('logging.default'))->warning('slow_request', [
                'duration_ms' => $durationMs,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $response->getStatusCode(),
                'ip' => $this->clientIpResolver->resolve($request),
                'user_agent' => substr($request->userAgent() ?? '', 0, 180),
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);
        }

        return $response;
    }

    private function isStaticAsset(string $path): bool
    {
        return (bool) preg_match('/\.(?:css|js|jpg|jpeg|png|gif|webp|svg|ico|woff2?|ttf|eot|map)$/i', $path);
    }
}
