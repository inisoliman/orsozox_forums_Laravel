<?php

namespace App\Services;

use App\Models\Forum;
use Illuminate\Support\Facades\DB;

class ForumCounterService
{
    public function rebuild(int $forumId): void
    {
        $threadQuery = DB::table('thread')->where('forumid', $forumId)->where('visible', 1);
        $threadCount = (clone $threadQuery)->count();
        $replyCount = DB::table('post as p')
            ->join('thread as t', 't.threadid', '=', 'p.threadid')
            ->where('t.forumid', $forumId)
            ->where('t.visible', 1)
            ->where('p.visible', 1)
            ->whereColumn('p.postid', '<>', 't.firstpostid')
            ->count();

        $last = DB::table('post as p')
            ->join('thread as t', 't.threadid', '=', 'p.threadid')
            ->where('t.forumid', $forumId)
            ->where('t.visible', 1)
            ->where('p.visible', 1)
            ->orderByDesc('p.dateline')
            ->orderByDesc('p.postid')
            ->select('p.postid', 'p.dateline', 'p.username', 't.threadid', 't.title')
            ->first();

        Forum::whereKey($forumId)->update([
            'threadcount' => $threadCount,
            'replycount' => $replyCount,
            'lastpost' => (int) ($last->dateline ?? 0),
            'lastpostid' => (int) ($last->postid ?? 0),
            'lastposter' => (string) ($last->username ?? ''),
            'lastthread' => (string) ($last->title ?? ''),
            'lastthreadid' => (int) ($last->threadid ?? 0),
        ]);
    }
}
