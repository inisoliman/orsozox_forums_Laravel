<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session as LaravelSession;
use Illuminate\Support\Facades\Auth;
use App\Models\Session as VBSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class UpdateLegacySession
{
    /**
     * User agents to skip (bots/crawlers)
     * Bingbot should NOT be throttled to avoid HTTP 429 errors
     */
    private array $botPatterns = [
        'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider',
        'YandexBot', 'Sogou', 'facebookexternalhit', 'Twitterbot',
        'rogerbot', 'linkedinbot', 'embedly', 'semrushbot', 'ahrefsbot',
        'dotbot', 'petalbot', 'MJ12bot', 'crawler', 'spider', 'bot/',
        'HeadlessChrome', 'Lighthouse', 'GTmetrix', 'PageSpeed',
    ];

    /**
     * Extensions to skip (static assets)
     */
    private array $skipExtensions = [
        'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'ico', 'woff', 'woff2', 'ttf', 'eot', 'map',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $userAgent = $request->userAgent() ?? '';
            $isBot = $this->isBot($userAgent);

            // Skip static asset requests (for both bots and humans)
            $path = $request->path();
            if ($this->isStaticAsset($path)) {
                return $response;
            }

            // Throttle: humans every 60s, bots every 5 minutes (lighter DB load),
            // keyed by IP + a bot/human flag so both are tracked separately.
            $throttleSeconds = $isBot ? 300 : 60;
            $throttleKey = 'session_update_' . ($isBot ? 'bot_' : '') . ($request->ip() ?? 'unknown');
            if (Cache::has($throttleKey)) {
                return $response;
            }
            Cache::put($throttleKey, true, $throttleSeconds);

            // Bots are now tracked so they appear in the "Who is online" page,
            // but they never get a Laravel session cookie — we always treat them
            // as guests (userid 0) and key their session row by IP + UserAgent.
            $this->updateSession($request, $isBot);
        } catch (\Exception $e) {
            // Fails silently to avoid breaking the site
        }


        return $response;
    }

    /**
     * Check if the user agent is a bot/crawler
     */
    protected function isBot(string $userAgent): bool
    {
        foreach ($this->botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the request is for a static asset
     */
    protected function isStaticAsset(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, $this->skipExtensions);
    }

    protected function updateSession(Request $request, bool $isBot = false)
    {
        $sessionHash = LaravelSession::get('vb_sessionhash');
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $path = $request->path();

        // Truncate User Agent to fit vB schema
        if (strlen($userAgent) > 100) {
            $userAgent = substr($userAgent, 0, 97) . '...';
        }

        // Bots never carry a session cookie. Track them directly by IP + UserAgent
        // so they show up in the "Who is online" page as crawlers.
        if ($isBot) {
            $this->upsertBotSession($request, $ip, $path, $userAgent);

            // Garbage Collector: 2% chance to clean old sessions
            if (rand(1, 100) <= 2) {
                VBSession::where('lastactivity', '<', time() - 900)->delete();
            }
            return;
        }

        if ($sessionHash) {

            // Try to find existing session
            $session = VBSession::where('sessionhash', $sessionHash)->first();

            if ($session) {
                // Update existing
                $session->lastactivity = time();
                $session->location = $path;
                $session->userid = Auth::id() ?? 0;
                $session->loggedin = Auth::check() ? 1 : 0;
                // Only update these if changed to reduce overhead
                if ($session->host !== $ip)
                    $session->host = $ip;

                $session->save();
            } else {
                // Session expired in DB but exists in Laravel cookie? Recreate
                $this->createSession($sessionHash, $request, $ip, $path, $userAgent);
            }
        } else {
            // Create new session
            $newHash = md5(microtime() . Str::random(10));
            LaravelSession::put('vb_sessionhash', $newHash);

            $this->createSession($newHash, $request, $ip, $path, $userAgent);
        }

        // Garbage Collector: 2% chance to clean old sessions
        if (rand(1, 100) <= 2) {
            $cutOff = time() - 900; // 15 minutes
            VBSession::where('lastactivity', '<', $cutOff)->delete();
        }
    }

    /**
     * Track a search-engine / crawler bot in the vBulletin session table.
     * Bots have no cookie, so we key the row deterministically by IP + UserAgent
     * (userid is always 0 / guest). This makes them appear in "Who is online".
     */
    protected function upsertBotSession($request, $ip, $path, $userAgent): void
    {
        $existing = VBSession::where('host', $ip)
            ->where('useragent', $userAgent)
            ->where('userid', 0)
            ->first();

        if ($existing) {
            $existing->lastactivity = time();
            $existing->location = $path;
            $existing->save();
            return;
        }

        $session = new VBSession();
        $session->sessionhash = md5('bot' . $ip . $userAgent);
        $session->userid = 0;
        $session->host = $ip;
        $session->idhash = md5($ip);
        $session->lastactivity = time();
        $session->location = $path;
        $session->useragent = $userAgent;
        $session->loggedin = 0;
        $session->badlocation = 0;
        $session->bypass = 0;
        $session->save();
    }

    protected function createSession($hash, $request, $ip, $path, $userAgent)
    {
        // Calculate idhash (vBulletin simple equivalent)
        $idhash = md5($ip);


        // Check if a session already exists for this IP/UserAgent to avoid duplicates for Guests
        if (!Auth::check()) {
            $existing = VBSession::where('host', $ip)
                ->where('useragent', $userAgent)
                ->where('userid', 0)
                ->first();

            if ($existing) {
                $existing->lastactivity = time();
                $existing->location = $path;
                $existing->save();
                LaravelSession::put('vb_sessionhash', $existing->sessionhash);
                return;
            }
        }

        $session = new VBSession();
        $session->sessionhash = $hash;
        $session->userid = Auth::id() ?? 0;
        $session->host = $ip;
        $session->idhash = $idhash;
        $session->lastactivity = time();
        $session->location = $path;
        $session->useragent = $userAgent;
        $session->loggedin = Auth::check() ? 1 : 0;
        $session->badlocation = 0;
        $session->bypass = 0;
        $session->save();
    }
}
