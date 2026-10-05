<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\ModerationPermissionService;

class PostPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Post $post)
    {
        if ($user->userid === (int) $post->userid) {
            return (int) $post->visible !== 2;
        }

        return app(ModerationPermissionService::class)->can($user, 'edit_post', null, $post);
    }
}
