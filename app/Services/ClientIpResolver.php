<?php

namespace App\Services;

use Illuminate\Http\Request;

class ClientIpResolver
{
    public function resolve(Request $request): string
    {
        $cloudflareIp = trim((string) $request->headers->get('CF-Connecting-IP', ''));
        $trustCloudflare = (bool) config('security.firewall.trust_cloudflare_headers', true);

        if (
            $trustCloudflare
            && $request->headers->has('CF-Ray')
            && filter_var($cloudflareIp, FILTER_VALIDATE_IP)
        ) {
            return $cloudflareIp;
        }

        return $request->ip() ?? 'unknown';
    }
}
