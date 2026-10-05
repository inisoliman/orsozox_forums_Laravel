<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Thread;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\ModerationPermissionService;

class ThreadPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Thread $thread)
    {
        if ($user->userid === (int) $thread->postuserid) {
            return (int) $thread->visible !== 2;
        }

        return app(ModerationPermissionService::class)->can($user, 'edit_thread', $thread);
    }
}
