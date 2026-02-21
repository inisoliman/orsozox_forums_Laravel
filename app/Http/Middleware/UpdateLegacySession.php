<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session as LaravelSession;
use Illuminate\Support\Facades\Auth;
use App\Models\Session as VBSession;
use Illuminate\Support\Str;

class UpdateLegacySession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->updateSession($request);
        } catch (\Exception $e) {
            // Fails silently to avoid breaking the site
        }

        return $response;
    }

    protected function updateSession(Request $request)
    {
        $sessionHash = LaravelSession::get('vb_sessionhash');
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $path = $request->path();

        // Truncate User Agent to fit vB schema
        if (strlen($userAgent) > 100) {
            $userAgent = substr($userAgent, 0, 97) . '...';
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

    protected function createSession($hash, $request, $ip, $path, $userAgent)
    {
        // Calculate idhash (vBulletin simple equivalent)
        // vB uses: md5($userAgent . $ip) usually, but we can just use a random string or the ip
        $idhash = md5($ip);

        // Check if a session already exists for this IP/UserAgent to avoid duplicates for Guests
        // Note: For guests, vBulletin allows multiple sessions per IP basically, but let's try to be clean
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
