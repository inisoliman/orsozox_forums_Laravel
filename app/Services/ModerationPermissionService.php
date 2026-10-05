<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Support\VBulletinModeratorPermissions as Bits;
use Illuminate\Database\Eloquent\Builder;

class ModerationPermissionService
{
    public function isAdministrator(?User $user): bool
    {
        return (bool) $user && $user->belongsToLegacyGroup((int) config('forum.administrator_usergroup_id', 6));
    }

    public function isSuperModerator(?User $user): bool
    {
        return (bool) $user && $user->belongsToLegacyGroup((int) config('forum.super_moderator_usergroup_id', 5));
    }

    public function isModerator(?User $user): bool
    {
        return (bool) $user && ($this->isAdministrator($user)
            || $this->isSuperModerator($user)
            || $user->belongsToLegacyGroup((int) config('forum.moderator_usergroup_id', 7)));
    }

    public function hasModeratorBit(?User $user, int $forumId, int $bit, string $field = 'permissions'): bool
    {
        if ($this->isAdministrator($user)) {
            return true;
        }

        if (! $user || ! $this->isModerator($user)) {
            return false;
        }

        return $user->moderatorAssignments()
            ->where(function ($query) use ($forumId) {
                $query->where('forumid', Bits::GLOBAL_FORUM_ID)
                    ->orWhere('forumid', $forumId);
            })
            ->get()
            ->contains(function ($assignment) use ($bit, $field) {
                return (((int) $assignment->{$field}) & $bit) === $bit;
            });
    }

    public function can(?User $user, string $ability, ?Thread $thread = null, ?Post $post = null): bool
    {
        if ($this->isAdministrator($user)) {
            return true;
        }

        if (! $thread && $post) {
            $thread = $post->relationLoaded('thread') ? $post->thread : $post->thread()->first();
        }
        $forumId = $thread?->forumid;
        if ($forumId === null || $forumId <= 0) {
            return false;
        }

        $map = [
            'edit_post' => [Bits::EDIT_POSTS, 'permissions'],
            'soft_delete_post' => [Bits::DELETE_POSTS, 'permissions'],
            'soft_delete_thread' => [Bits::DELETE_POSTS, 'permissions'],
            'hard_delete_post' => [Bits::REMOVE_POSTS, 'permissions'],
            'open_close' => [Bits::OPEN_CLOSE, 'permissions'],
            'edit_thread' => [Bits::EDIT_THREADS, 'permissions'],
            'manage_thread' => [Bits::MANAGE_THREADS, 'permissions'],
            'moderate_post' => [Bits::MODERATE_POSTS, 'permissions'],
            'moderate_attachment' => [Bits::MODERATE_ATTACHMENTS, 'permissions'],
            'mass_move' => [Bits::MASS_MOVE, 'permissions'],
            'mass_prune' => [Bits::MASS_PRUNE, 'permissions'],
            'hard_remove_post' => [Bits::REMOVE_POSTS, 'permissions'],
            'moderate_visitor_message' => [Bits::MODERATE_VISITOR_MESSAGES, 'permissions2'],
            'delete_visitor_message' => [Bits::DELETE_VISITOR_MESSAGES, 'permissions2'],
            'remove_visitor_message' => [Bits::REMOVE_VISITOR_MESSAGES, 'permissions2'],
        ];

        if (! isset($map[$ability])) {
            return false;
        }

        [$bit, $field] = $map[$ability];
        return $this->hasModeratorBit($user, (int) $forumId, $bit, $field);
    }

    public function canSoftDeleteThread(?User $user, Thread $thread): bool
    {
        return $this->can($user, 'soft_delete_thread', $thread);
    }

    public function canHardDeleteThread(?User $user, Thread $thread): bool
    {
        return $this->isAdministrator($user);
    }

    public function canRestoreThread(?User $user, Thread $thread): bool
    {
        return $this->can($user, 'soft_delete_thread', $thread);
    }

    public function canMoveThread(?User $user, Thread $thread, bool $bulk = false): bool
    {
        return $this->can($user, $bulk ? 'mass_move' : 'manage_thread', $thread);
    }

    public function canMergeThread(?User $user, Thread $thread): bool
    {
        return $this->can($user, 'manage_thread', $thread);
    }

    public function canManageForum(?User $user, int $forumId, bool $bulkMove = false): bool
    {
        if ($this->isAdministrator($user)) return true;
        return $this->hasModeratorBit($user, $forumId, $bulkMove ? Bits::MASS_MOVE : Bits::MANAGE_THREADS);
    }

    public function canOpenClose(?User $user, Thread $thread): bool
    {
        return $this->can($user, 'open_close', $thread);
    }

    public function canModeratePost(?User $user, Post $post): bool
    {
        return $this->can($user, 'moderate_post', null, $post);
    }

    public function canModerateVisitorMessage(?User $user): bool
    {
        if ($this->isAdministrator($user)) return true;
        if (! $user || ! $this->isModerator($user)) return false;

        return $user->moderatorAssignments()->where('forumid', Bits::GLOBAL_FORUM_ID)->get()->contains(function ($assignment) {
            return (((int) $assignment->permissions2) & Bits::MODERATE_VISITOR_MESSAGES) === Bits::MODERATE_VISITOR_MESSAGES;
        });
    }

    /**
     * Restrict a moderation query to forums assigned to the actor.
     * Administrators keep the unmodified query; scoped moderators may see
     * global assignments (forumid=-1) or only their assigned forums.
     */
    public function scopeToPermittedForums(Builder $query, ?User $user, string $forumColumn = 'forumid'): Builder
    {
        if ($this->isAdministrator($user)) {
            return $query;
        }

        if (! $user || ! $this->isModerator($user)) {
            return $query->whereRaw('1 = 0');
        }

        $assignments = $user->moderatorAssignments();
        if ((clone $assignments)->where('forumid', Bits::GLOBAL_FORUM_ID)->exists()) {
            return $query;
        }

        return $query->whereIn($forumColumn, (clone $assignments)->select('forumid'));
    }

}
