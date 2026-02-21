<?php

namespace App\Services;

use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OnlineUsersService
{
    protected $botAgents = [
        'Googlebot',
        'Bingbot',
        'Slurp',
        'DuckDuckBot',
        'Baiduspider',
        'YandexBot',
        'Sogou',
        'Exabot',
        'facebookexternalhit',
        'facebot',
        'ia_archiver',
        'Twitterbot'
    ];

    public function getOnlineUsers()
    {
        // 15 minutes timeout
        $cutOff = Carbon::now()->subMinutes(15)->timestamp;

        $sessions = Session::where('lastactivity', '>', $cutOff)
            ->with('user')
            ->orderBy('lastactivity', 'desc')
            ->get();

        $members = [];
        $guests = [];
        $bots = [];

        foreach ($sessions as $session) {
            $data = [
                'ip_address' => $session->host,
                'user_agent' => $session->useragent,
                'last_activity' => Carbon::createFromTimestamp($session->lastactivity)->diffForHumans(),
                'location' => $this->parseLocation($session->location),
                'browser' => $this->getBrowser($session->useragent),
            ];

            if ($this->isBot($session->useragent)) {
                $bots[] = $data;
            } elseif ($session->userid > 0 && $session->user) {
                $data['user'] = $session->user;
                $members[] = $data;
            } else {
                $guests[] = $data;
            }
        }

        return [
            'total' => count($sessions),
            'members' => $members,
            'guests' => $guests,
            'bots' => $bots,
        ];
    }

    protected function isBot($userAgent)
    {
        foreach ($this->botAgents as $bot) {
            if (Str::contains($userAgent, $bot, true)) {
                return true;
            }
        }
        return false;
    }

    protected function parseLocation($location)
    {
        // Example: /forums/showthread.php?t=123 (vBulletin style)
        // or /forums/thread/123/slug (Laravel style)

        if (empty($location))
            return 'الصفحة الرئيسية';

        if (Str::contains($location, 'showthread.php') || Str::contains($location, '/thread/')) {
            return 'يشاهد موضوع';
        }

        if (Str::contains($location, 'forumdisplay.php') || Str::contains($location, '/forum/')) {
            return 'يتصفح قسم';
        }

        if (Str::contains($location, 'member.php') || Str::contains($location, '/user/')) {
            return 'ملف عضو';
        }

        if (Str::contains($location, 'search')) {
            return 'يبحث في المنتدى';
        }

        return 'يتصفح الموقع';
    }

    protected function getBrowser($userAgent)
    {
        if (Str::contains($userAgent, 'Chrome'))
            return 'Chrome';
        if (Str::contains($userAgent, 'Firefox'))
            return 'Firefox';
        if (Str::contains($userAgent, 'Safari') && !Str::contains($userAgent, 'Chrome'))
            return 'Safari';
        if (Str::contains($userAgent, 'Edge'))
            return 'Edge';
        if (Str::contains($userAgent, 'Opera') || Str::contains($userAgent, 'OPR'))
            return 'Opera';

        return 'Browser';
    }
}
