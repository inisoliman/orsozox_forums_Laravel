<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\FirewallDecisionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lightweight bot/crawler protection for shared LiteSpeed hosting.
 *
 * Goal: reject obviously abusive automated traffic before controllers run,
 * so Laravel avoids expensive DB queries and Blade rendering under crawler spikes.
 */
class BotTrafficProtectionMiddleware
{
    public function __construct(private readonly FirewallDecisionService $firewall)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!(bool) config('security.firewall.enabled', true)) {
            return $next($request);
        }

        if ($this->isStaticAsset($request->path())) {
            return $next($request);
        }

        $decision = $this->firewall->decide($request);
        if ($decision['action'] === 'deny') {
            return response('Temporary cooldown: server protection is active.', $decision['status'])
                ->header('Retry-After', (string) $decision['retry_after'])
                ->header('X-Forum-Firewall', $decision['class'] . ':' . $decision['reason'])
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        $response = $next($request);
        $response->headers->set('X-Forum-Traffic-Class', $decision['class']);

        return $response;
    }

    private function isStaticAsset(string $path): bool
    {
        return (bool) preg_match('/\.(?:css|js|jpg|jpeg|png|gif|webp|svg|ico|woff2?|ttf|eot|map)$/i', $path);
    }
}