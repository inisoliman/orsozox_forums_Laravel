<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\VisitorMessage;
use Illuminate\Support\Facades\DB;

/**
 * خدمة مراجعة المحتوى — تُستخدم من لوحة Filament ومن أزرار الموافقة داخل المنتدى.
 *
 * القاعدة: عند إنشاء عنصر غير مرئي (موضوع/رد) لم تُزدَد العدادات في المصدر،
 * لذلك الموافقة هنا تُكمّل الناقص فقط (threadcount / posts / replycount / lastpost).
 * رسائل الزوار لا تملك عدّادات في الجداول المتاحة، فنكتفي بتغيير الحالة.
 */
class ModerationService
{
    public const THRESHOLD = 80; // مطابق لـ SpamShieldService

    /**
     * الموافقة على نشر موضوع قيد المراجعة.
     */
    public function approveThread(int $threadId): bool
    {
        return (bool) DB::transaction(function () use ($threadId) {
            $thread = Thread::whereKey($threadId)->first();
            if (! $thread || (int) $thread->visible === 1) {
                return false;
            }

            // نشر أول مشاركة (المحتوى الأساسي)
            if ($thread->firstpostid) {
                Post::whereKey($thread->firstpostid)->update(['visible' => 1]);
            }

            $thread->visible = 1;
            $thread->save();

            Forum::whereKey($thread->forumid)->increment('threadcount');

            if ($thread->postuserid) {
                User::whereKey($thread->postuserid)->increment('posts');
            }

            return true;
        });
    }

    /**
     * الموافقة على نشر رد قيد المراجعة.
     */
    public function approvePost(int $postId): bool
    {
        return (bool) DB::transaction(function () use ($postId) {
            $post = Post::whereKey($postId)->first();
            if (! $post || (int) $post->visible === 1) {
                return false;
            }

            $post->visible = 1;
            $post->save();

            Thread::whereKey($post->threadid)->update([
                'replycount' => DB::raw('replycount + 1'),
                'lastpost' => (int) $post->dateline,
            ]);

            if ($post->userid) {
                User::whereKey($post->userid)->increment('posts');
            }

            return true;
        });
    }

    /**
     * الموافقة على نشر رسالة زائر قيد المراجعة.
     */
    public function approveVisitorMessage(int $vmid): bool
    {
        $updated = VisitorMessage::whereKey($vmid)
            ->where('state', 'moderation')
            ->update(['state' => 'visible']);

        return (bool) $updated;
    }

    /**
     * كل المواضيع قيد المراجعة (visible = 0).
     */
    public function pendingThreads()
    {
        return Thread::where('visible', 0)->orderBy('dateline', 'desc')->get();
    }

    /**
     * كل الردود قيد المراجعة (visible = 0 وباستثناء المشاركة الأولى).
     */
    public function pendingPosts()
    {
        return Post::where('visible', 0)
            ->whereNotIn('postid', function ($query) {
                $query->select('firstpostid')->from('thread')->whereNotNull('firstpostid');
            })
            ->orderBy('dateline', 'desc')
            ->get();
    }

    /**
     * كل رسائل الزوار قيد المراجعة (state = moderation).
     */
    public function pendingVisitorMessages()
    {
        return VisitorMessage::where('state', 'moderation')->orderBy('dateline', 'desc')->get();
    }
}
