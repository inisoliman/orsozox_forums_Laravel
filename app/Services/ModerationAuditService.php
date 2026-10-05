<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ModerationAuditService
{
    public function recordDeletion(int $primaryId, string $type, User $actor, string $reason = ''): void
    {
        DB::table('deletionlog')->updateOrInsert(
            ['primaryid' => $primaryId, 'type' => $type],
            [
                'userid' => $actor->userid,
                'username' => $actor->username,
                'reason' => mb_substr($reason, 0, 125),
                'dateline' => time(),
            ]
        );
    }

    public function recordAction(string $action, User $actor, ?Thread $thread = null, ?Post $post = null, array $ids = []): void
    {
        DB::table('moderatorlog')->insert([
            'dateline' => time(),
            'userid' => $actor->userid,
            'forumid' => (int) ($thread?->forumid ?? $post?->thread?->forumid ?? 0),
            'threadid' => (int) ($thread?->threadid ?? $post?->threadid ?? 0),
            'postid' => (int) ($post?->postid ?? 0),
            'pollid' => 0,
            'attachmentid' => 0,
            'action' => mb_substr($action, 0, 250),
            'type' => 0,
            'threadtitle' => mb_substr((string) ($thread?->title ?? ''), 0, 250),
            'ipaddress' => request()->ip() ?? '',
            'product' => 'forums-live',
            'id1' => (int) ($ids[0] ?? 0),
            'id2' => (int) ($ids[1] ?? 0),
            'id3' => (int) ($ids[2] ?? 0),
            'id4' => (int) ($ids[3] ?? 0),
            'id5' => (int) ($ids[4] ?? 0),
        ]);
    }
}
