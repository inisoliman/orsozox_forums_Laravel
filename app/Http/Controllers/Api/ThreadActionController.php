<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\ModerationActionService;
use App\Services\ModerationPermissionService;
use RuntimeException;

class ThreadActionController extends Controller
{
    public function __construct(
        private readonly ModerationActionService $actions,
        private readonly ModerationPermissionService $permissions,
    ) {
    }
    /**
     * التحقق من الصلاحيات (أدمن، مشرف، أو كاتب الموضوع)
     */
    protected function authorizeAction(Thread $thread)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'غير مصرح لك.');
        }

        if ($user->can('update', $thread)) {
            return true;
        }

        abort(403, 'ليس لديك صلاحية لتعديل هذا الموضوع.');
    }

    /**
     * تعديل الموضوع (العنوان والمحتوى) عبر AJAX
     */
    public function update(Request $request, $id)
    {
        $thread = Thread::findOrFail($id);
        $this->authorizeAction($thread);

        $request->validate([
            'title' => 'required|string|max:255',
            'pagetext' => 'required|string',
        ]);

        // تحديث العنوان
        $thread->title = $request->title;
        $thread->save();

        // تحديث محتوى الموضوع (الرد الأول)
        if ($thread->firstPost) {
            // إضافة HTML Marker بحيث يتم التعرف عليه كمحتوى غني
            $pagetext = '<!-- HTML -->' . $request->pagetext;
            $thread->firstPost->update(['pagetext' => $pagetext]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ التعديلات بنجاح!',
            'title' => $thread->title,
            'content' => \App\Helpers\BBCodeParser::parse($thread->firstPost->pagetext ?? '')
        ]);
    }

    /**
     * نقل الموضوع إلى قسم آخر عبر AJAX
     */
    public function move(Request $request, $id)
    {
        $thread = Thread::findOrFail($id);
        abort_unless($this->permissions->canMoveThread($request->user(), $thread), 403, 'ليس لديك صلاحية نقل الموضوع.');

        $request->validate([
            'forumid' => 'required|exists:forum,forumid',
        ]);

        $this->actions->moveThread($thread, (int) $request->forumid, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم نقل الموضوع بنجاح.',
            'redirect' => route('thread.show', ['id' => $thread->threadid, 'slug' => $thread->slug])
        ]);
    }

    /**
     * حذف الموضوع وجميع ردوده عبر AJAX
     */
    public function destroy($id)
    {
        $thread = Thread::findOrFail($id);
        $actor = request()->user();
        abort_unless($this->permissions->canSoftDeleteThread($actor, $thread), 403, 'ليس لديك صلاحية حذف الموضوع.');
        $forumId = $thread->forumid;
        $this->actions->softDeleteThread($thread, $actor, (string) request()->input('reason', ''));

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الموضوع حذفاً بسيطاً ويمكن استعادته.',
            'redirect' => route('forum.show', ['id' => $forumId])
        ]);
    }

    public function restore(Request $request, int $id)
    {
        $thread = Thread::findOrFail($id);
        $this->actions->restoreThread($thread, $request->user());
        return response()->json(['success' => true, 'message' => 'تمت استعادة الموضوع.', 'redirect' => $thread->url]);
    }

    public function hardDelete(Request $request, int $id)
    {
        $request->validate(['confirmed' => ['required', 'accepted']]);
        $thread = Thread::findOrFail($id);
        $forumId = (int) $thread->forumid;
        $this->actions->hardDeleteThread($thread, $request->user());
        return response()->json(['success' => true, 'message' => 'تم حذف الموضوع نهائياً.', 'redirect' => route('forum.show', ['id' => $forumId])]);
    }

    public function sticky(Request $request, int $id)
    {
        $thread = Thread::findOrFail($id);
        $data = $request->validate(['sticky' => ['required', 'boolean']]);
        $this->actions->setSticky($thread, (bool) $data['sticky'], $request->user());
        return response()->json(['success' => true, 'message' => $data['sticky'] ? 'تم تثبيت الموضوع.' : 'تم إلغاء تثبيت الموضوع.']);
    }

    public function open(Request $request, int $id)
    {
        $thread = Thread::findOrFail($id);
        $data = $request->validate(['open' => ['required', 'boolean']]);
        $this->actions->setOpen($thread, (bool) $data['open'], $request->user());
        return response()->json(['success' => true, 'message' => $data['open'] ? 'تم فتح الموضوع.' : 'تم إغلاق الموضوع.']);
    }

    /**
     * دمج موضوع في موضوع هدف عبر AJAX
     */
    public function merge(Request $request, int $id)
    {
        $thread = Thread::findOrFail($id);
        $data = $request->validate([
            'target_thread_id' => ['required', 'integer', 'exists:thread,threadid'],
        ]);
        $target = Thread::findOrFail($data['target_thread_id']);
        $this->actions->mergeThreads($target, [$thread->threadid], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم دمج الموضوع في الموضوع الهدف.',
            'redirect' => $target->url,
        ]);
    }

    /**
     * نقل رد أو عدة ردود إلى موضوع هدف عبر AJAX
     */
    public function movePosts(Request $request, int $id)
    {
        $thread = Thread::findOrFail($id);
        $data = $request->validate([
            'post_ids' => ['required', 'array', 'min:1', 'max:100'],
            'post_ids.*' => ['integer', 'distinct', 'exists:post,postid'],
            'target_thread_id' => ['required', 'integer', 'exists:thread,threadid'],
        ]);
        $target = Thread::findOrFail($data['target_thread_id']);
        $this->actions->movePosts($data['post_ids'], $target, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم نقل الردود إلى الموضوع الهدف.',
            'redirect' => $target->url,
        ]);
    }
}
