<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Thread;
use App\Models\Forum;
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

        // Fetch totals mapping (Members, Guests, Bots)
        $allActiveSessions = Session::where('lastactivity', '>', $cutOff)->get(['userid', 'useragent']);

        $totalMembers = 0;
        $totalGuests = 0;
        $totalBots = 0;

        foreach ($allActiveSessions as $s) {
            if ($this->isBot($s->useragent)) {
                $totalBots++;
            } elseif ($s->userid > 0) {
                $totalMembers++;
            } else {
                $totalGuests++;
            }
        }

        // Fetch Paginated Main Data
        $sessions = Session::where('lastactivity', '>', $cutOff)
            ->with('user')
            ->orderBy('lastactivity', 'desc')
            ->paginate(20);

        // Pre-fetch locations to avoid N+1
        $threadIds = [];
        $forumIds = [];

        foreach ($sessions as $session) {
            if (empty($session->location)) {
                continue;
            }
            if (preg_match('/(?:showthread\.php\?t=|thread\/)(\d+)/', $session->location, $matches)) {
                $threadIds[] = $matches[1];
            } elseif (preg_match('/(?:forumdisplay\.php\?f=|forum\/)(\d+)/', $session->location, $matches)) {
                $forumIds[] = $matches[1];
            }
        }

        $threadsList = count($threadIds) > 0 ? Thread::whereIn('threadid', array_unique($threadIds))->get(['threadid', 'title', 'forumid'])->keyBy('threadid') : collect([]);
        $forumsList = count($forumIds) > 0 ? Forum::whereIn('forumid', array_unique($forumIds))->get(['forumid', 'title', 'parentid'])->keyBy('forumid') : collect([]);

        $usersList = [];

        foreach ($sessions as $session) {
            $lastActivityInt = (int) $session->getRawOriginal('lastactivity');
            $lastActivity = Carbon::createFromTimestamp($lastActivityInt)->timezone(config('app.timezone'));

            $type = 'guest';
            if ($this->isBot($session->useragent)) {
                $type = 'bot';
            } elseif ($session->userid > 0 && $session->user) {
                $type = 'member';
            }

            $usersList[] = [
                'type' => $type,
                'user' => $session->user ?? null,
                'ip_address' => $session->host,
                'user_agent' => $session->useragent,
                'last_activity' => $lastActivity->diffForHumans(),
                'location' => $this->parseLocationData($session->location, $threadsList, $forumsList),
                'browser' => $this->getBrowser($session->useragent),
            ];
        }

        return [
            'total' => $totalMembers + $totalGuests + $totalBots,
            'total_members' => $totalMembers,
            'total_guests' => $totalGuests,
            'total_bots' => $totalBots,
            'users' => $usersList,
            'paginator' => $sessions,
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

    protected function parseLocationData($location, $threadsList, $forumsList)
    {
        $data = ['text' => 'يتصفح الموقع', 'url' => null];

        if (empty($location)) {
            $data['text'] = 'الصفحة الرئيسية';
            $data['url'] = route('home');
            return $data;
        }

        if (preg_match('/(?:showthread\.php\?t=|thread\/)(\d+)/', $location, $matches)) {
            $threadId = $matches[1];
            if ($threadsList->has($threadId)) {
                $thread = $threadsList->get($threadId);
                $data['text'] = 'يشاهد: ' . Str::limit($thread->title, 40);
                $data['url'] = $thread->url;
            } else {
                $data['text'] = 'يشاهد موضوع';
            }
            return $data;
        }

        if (preg_match('/(?:forumdisplay\.php\?f=|forum\/)(\d+)/', $location, $matches)) {
            $forumId = $matches[1];
            if ($forumsList->has($forumId)) {
                $forum = $forumsList->get($forumId);
                $data['text'] = 'يتصفح: ' . Str::limit($forum->title, 40);
                $data['url'] = $forum->url;
            } else {
                $data['text'] = 'يتصفح قسم';
            }
            return $data;
        }

        if (preg_match('/(?:member\.php\?u=|user\/)(\d+)/', $location, $matches)) {
            $userId = $matches[1];
            $data['text'] = 'ملف عضو';
            $data['url'] = route('user.show', ['id' => $userId]);
            return $data;
        }

        if (Str::contains($location, 'search')) {
            $data['text'] = 'يبحث في المنتدى';
            $data['url'] = route('search');
            return $data;
        }

        return $data;
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
