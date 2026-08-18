<?php

return [
    'firewall' => [
        'enabled' => env('FORUM_FIREWALL_ENABLED', true),
        'survival_mode_threshold_per_minute' => (int) env('SURVIVAL_MODE_REQUESTS_PER_MINUTE', 240),
        'survival_mode_ttl' => (int) env('SURVIVAL_MODE_TTL', 300),
        'temporary_ban_ttl' => (int) env('FIREWALL_TEMP_BAN_TTL', 900),
        'cooldown_ttl' => (int) env('FIREWALL_COOLDOWN_TTL', 120),
        'verified_bot_dns_cache_ttl' => (int) env('VERIFIED_BOT_DNS_CACHE_TTL', 86400),
        'trust_cloudflare_headers' => (bool) env('TRUST_CLOUDFLARE_HEADERS', true),
        'quick_reply_min_chars' => (int) env('QUICK_REPLY_MIN_CHARS', 10),
        'quick_reply_max_chars' => (int) env('QUICK_REPLY_MAX_CHARS', 10000),
    ],
];
