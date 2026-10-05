<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\Attachment;
use App\Models\Forum;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Closure;

class ModerationActionService
{
    public function __construct(
        private readonly ModerationPermissionService $permissions,
        private readonly ModerationAuditService $audit,
        private readonly ForumCounterService $forumCounters,
        private readonly ThreadCounterService $threadCounters,
    ) {
    }

    public function softDeleteThread(Thread $thread, User $actor, string $reason = ''): void
    {
        if (! $this->permissions->canSoftDeleteThread($actor, $thread)) {
            throw new RuntimeException('ليس لديك صلاحية حذف الموضوع.');
        }

        $this->transaction(function () use ($thread, $actor, $reason) {
            if ((int) $thread->visible === 2) return;
            $thread->update(['visible' => 2]);
            $this->audit->recordDeletion($thread->threadid, 'thread', $actor, $reason);
            $this->audit->recordAction('soft_delete_thread', $actor, $thread);
            $this->forumCounters->rebuild((int) $thread->forumid);
        });
    }

    public function restoreThread(Thread $thread, User $actor): void
    {
        if (! $this->permissions->canRestoreThread($actor, $thread)) {
            throw new RuntimeException('ليس لديك صلاحية استعادة الموضوع.');
        }

        $this->transaction(function () use ($thread, $actor) {
            if ((int) $thread->visible !== 2) return;
            $thread->update(['visible' => 1]);
            DB::table('deletionlog')->where('primaryid', $thread->threadid)->where('type', 'thread')->delete();
            $this->audit->recordAction('restore_thread', $actor, $thread);
            $this->forumCounters->rebuild((int) $thread->forumid);
        });
    }

    public function hardDeleteThread(Thread $thread, User $actor): void
    {
        if (! $this->permissions->canHardDeleteThread($actor, $thread)) {
            throw new RuntimeException('الحذف الفعلي متاح للأدمن فقط.');
        }

        $this->transaction(function () use ($thread, $actor) {
            $forumId = (int) $thread->forumid;
            $this->audit->recordAction('hard_delete_thread', $actor, $thread);
            $postIds = Post::where('threadid', $thread->threadid)->pluck('postid');
            Attachment::whereIn('postid', $postIds)->delete();
            DB::table('deletionlog')->where('primaryid', $thread->threadid)->where('type', 'thread')->delete();
            Post::whereIn('postid', $postIds)->delete();
            $thread->delete();
            $this->forumCounters->rebuild($forumId);
        });
    }

    public function softDeletePost(Post $post, User $actor, string $reason = ''): void
    {
        $thread = $post->thread;
        if (! $thread || ! $this->permissions->can($actor, 'soft_delete_post', $thread, $post)) {
            throw new RuntimeException('ليس لديك صلاحية حذف الرد.');
        }

        $this->transaction(function () use ($post, $thread, $actor, $reason) {
            if ((int) $post->visible === 2) return;
            $post->update(['visible' => 2]);
            $this->audit->recordDeletion($post->postid, 'post', $actor, $reason);
            $this->audit->recordAction('soft_delete_post', $actor, $thread, $post);
            $this->threadCounters->rebuild((int) $thread->threadid);
            $this->forumCounters->rebuild((int) $thread->forumid);
        });
    }

    public function restorePost(Post $post, User $actor): void
    {
        $thread = $post->thread;
        if (! $thread || ! $this->permissions->can($actor, 'soft_delete_post', $thread, $post)) {
            throw new RuntimeException('ليس لديك صلاحية استعادة الرد.');
        }

        $this->transaction(function () use ($post, $thread, $actor) {
            if ((int) $post->visible !== 2) return;
            $post->update(['visible' => 1]);
            DB::table('deletionlog')->where('primaryid', $post->postid)->where('type', 'post')->delete();
            $this->audit->recordAction('restore_post', $actor, $thread, $post);
            $this->threadCounters->rebuild((int) $thread->threadid);
            $this->forumCounters->rebuild((int) $thread->forumid);
        });
    }

    public function hardDeletePost(Post $post, User $actor): void
    {
        if (! $this->permissions->isAdministrator($actor)) {
            throw new RuntimeException('الحذف الفعلي متاح للأدمن فقط.');
        }

        $this->transaction(function () use ($post, $actor) {
            $thread = $post->thread;
            if ($thread && (int) $thread->firstpostid === (int) $post->postid) {
                throw new RuntimeException('لا يمكن حذف المشاركة الأولى منفردة؛ استخدم حذف الموضوع.');
            }
            $this->audit->recordAction('hard_delete_post', $actor, $thread, $post);
            Attachment::where('postid', $post->postid)->delete();
            DB::table('deletionlog')->where('primaryid', $post->postid)->where('type', 'post')->delete();
            $post->delete();
            if ($thread) {
                $this->threadCounters->rebuild((int) $thread->threadid);
                $this->forumCounters->rebuild((int) $thread->forumid);
            }
        });
    }

    public function moveThread(Thread $thread, int $targetForumId, User $actor, bool $bulk = false): void
    {
        if (! $this->permissions->canMoveThread($actor, $thread, $bulk)) {
            throw new RuntimeException('ليس لديك صلاحية نقل الموضوع.');
        }
        if (! $this->permissions->canManageForum($actor, $targetForumId, $bulk)) {
            throw new RuntimeException('ليس لديك صلاحية النقل إلى القسم الهدف.');
        }
        if ((int) $thread->forumid === $targetForumId) return;
        if (! Forum::active()->whereKey($targetForumId)->exists()) {
            throw new RuntimeException('القسم الهدف غير نشط أو غير صالح للنقل.');
        }

        $this->transaction(function () use ($thread, $targetForumId, $actor) {
            $sourceForumId = (int) $thread->forumid;
            $thread->update(['forumid' => $targetForumId]);
            $this->audit->recordAction('move_thread', $actor, $thread, null, [$sourceForumId, $targetForumId]);
            $this->forumCounters->rebuild($sourceForumId);
            $this->forumCounters->rebuild($targetForumId);
        });
    }

    public function setSticky(Thread $thread, bool $sticky, User $actor): void
    {
        if (! $this->permissions->canMergeThread($actor, $thread)) {
            throw new RuntimeException('ليس لديك صلاحية تثبيت الموضوع.');
        }
        $this->transaction(function () use ($thread, $sticky, $actor) {
            $thread->update(['sticky' => $sticky ? 1 : 0]);
            $this->audit->recordAction($sticky ? 'stick_thread' : 'unstick_thread', $actor, $thread);
        });
    }

    public function setOpen(Thread $thread, bool $open, User $actor): void
    {
        if (! $this->permissions->canOpenClose($actor, $thread)) {
            throw new RuntimeException('ليس لديك صلاحية فتح أو إغلاق الموضوع.');
        }
        $this->transaction(function () use ($thread, $open, $actor) {
            $thread->update(['open' => $open ? 1 : 0]);
            $this->audit->recordAction($open ? 'open_thread' : 'close_thread', $actor, $thread);
        });
    }

    public function movePosts(array $postIds, Thread $target, User $actor): void
    {
        if (! $this->permissions->canMergeThread($actor, $target)) {
            throw new RuntimeException('ليس لديك صلاحية نقل الردود.');
        }

        $postIds = array_values(array_unique(array_map('intval', $postIds)));
        if ($postIds === []) {
            throw new RuntimeException('لم يتم اختيار ردود للنقل.');
        }

        $this->transaction(function () use ($postIds, $target, $actor) {
            $posts = Post::with('thread')->whereIn('postid', $postIds)->lockForUpdate()->get();
            if ($posts->count() !== count($postIds)) {
                throw new RuntimeException('تعذر العثور على جميع الردود المحددة.');
            }
            $sourceThreadIds = [];
            $forumIds = [(int) $target->forumid];
            foreach ($posts as $post) {
                if (! $post->thread || ! $this->permissions->canMergeThread($actor, $post->thread)) {
                    throw new RuntimeException('لا تملك صلاحية إدارة أحد الردود المحددة.');
                }
                if ((int) $post->postid === (int) $post->thread->firstpostid) {
                    throw new RuntimeException('لا يمكن نقل المشاركة الأولى بهذه العملية.');
                }
                $sourceThreadIds[] = (int) $post->threadid;
                $forumIds[] = (int) $post->thread->forumid;
                $parentId = (int) $post->parentid;
                $parentBelongsToTarget = $parentId > 0 && Post::where('postid', $parentId)
                    ->where('threadid', $target->threadid)
                    ->exists();
                $post->update([
                    'threadid' => $target->threadid,
                    'parentid' => $parentBelongsToTarget ? $parentId : 0,
                ]);
            }
            foreach (array_unique($sourceThreadIds) as $sourceId) $this->threadCounters->rebuild($sourceId);
            $this->threadCounters->rebuild((int) $target->threadid);
            foreach (array_unique($forumIds) as $forumId) {
                $this->forumCounters->rebuild($forumId);
            }
            $this->audit->recordAction('move_posts', $actor, $target, null, array_slice($postIds, 0, 5));
        });
    }

    public function mergeThreads(Thread $target, array $sourceThreadIds, User $actor): void
    {
        if (! $this->permissions->canMergeThread($actor, $target)) {
            throw new RuntimeException('ليس لديك صلاحية دمج المواضيع.');
        }

        $sourceThreadIds = array_values(array_unique(array_map('intval', $sourceThreadIds)));
        if ($sourceThreadIds === [] || in_array((int) $target->threadid, $sourceThreadIds, true)) {
            throw new RuntimeException('يجب اختيار موضوع مصدر مختلف عن الموضوع الهدف.');
        }

        $this->transaction(function () use ($target, $sourceThreadIds, $actor) {
            $sources = Thread::whereIn('threadid', $sourceThreadIds)->lockForUpdate()->get();
            if ($sources->count() !== count($sourceThreadIds)) {
                throw new RuntimeException('تعذر العثور على جميع المواضيع المصدر.');
            }
            $forumIds = [(int) $target->forumid];
            foreach ($sources as $source) {
                if (! $this->permissions->canMergeThread($actor, $source)) {
                    throw new RuntimeException('لا تملك صلاحية إدارة أحد المواضيع المحددة.');
                }
                $forumIds[] = (int) $source->forumid;
                $sourcePostIds = Post::where('threadid', $source->threadid)->pluck('postid');
                Post::whereIn('postid', $sourcePostIds)
                    ->update(['threadid' => $target->threadid]);
                foreach (Post::whereIn('postid', $sourcePostIds)->get() as $movedPost) {
                    $parentIsInTarget = (int) $movedPost->parentid > 0
                        && Post::where('postid', $movedPost->parentid)
                            ->where('threadid', $target->threadid)
                            ->exists();
                    if (! $parentIsInTarget && (int) $movedPost->parentid !== 0) {
                        $movedPost->update(['parentid' => 0]);
                    }
                }
                $this->softDeleteThreadWithinTransaction($source, $actor, 'تم الدمج في الموضوع رقم ' . $target->threadid);
            }
            $this->threadCounters->rebuild((int) $target->threadid);
            $forumIds[] = (int) $target->forumid;
            foreach (array_unique($forumIds) as $forumId) {
                $this->forumCounters->rebuild($forumId);
            }
            $this->audit->recordAction('merge_threads', $actor, $target, null, array_slice($sourceThreadIds, 0, 5));
        });
    }

    private function softDeleteThreadWithinTransaction(Thread $thread, User $actor, string $reason): void
    {
        if ((int) $thread->visible === 2) {
            return;
        }

        $thread->update(['visible' => 2]);
        $this->audit->recordDeletion($thread->threadid, 'thread', $actor, $reason);
        $this->audit->recordAction('soft_delete_thread', $actor, $thread);
        $this->forumCounters->rebuild((int) $thread->forumid);
    }

    private function transaction(Closure $callback): mixed
    {
        return DB::transactionLevel() > 0 ? $callback() : DB::transaction($callback);
    }
}
