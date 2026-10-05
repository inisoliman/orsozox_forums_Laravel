<?php

namespace App\Services;

use App\Models\Thread;
use Illuminate\Support\Facades\DB;

class ThreadCounterService
{
    public function rebuild(int $threadId): void
    {
        $thread = Thread::whereKey($threadId)->first();
        if (! $thread) return;

        $last = DB::table('post')
            ->where('threadid', $threadId)
            ->where('visible', 1)
            ->orderByDesc('dateline')
            ->orderByDesc('postid')
            ->first();

        $replyCount = DB::table('post')
            ->where('threadid', $threadId)
            ->where('visible', 1)
            ->where('postid', '<>', $thread->firstpostid)
            ->count();

        $thread->update([
            'replycount' => $replyCount,
            'lastpost' => (int) ($last->dateline ?? 0),
            'lastpostid' => (int) ($last->postid ?? 0),
            'lastposter' => (string) ($last->username ?? ''),
        ]);
    }
}
