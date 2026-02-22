<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ThreadActionController extends Controller
{
    /**
     * التحقق من الصلاحيات (أدمن، مشرف، أو كاتب الموضوع)
     */
    protected function authorizeAction(Thread $thread)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'غير مصرح لك.');
        }

        if ($user->is_admin || $user->is_moderator || $user->userid === $thread->postuserid) {
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
        $this->authorizeAction($thread);

        $request->validate([
            'forumid' => 'required|exists:forum,forumid',
        ]);

        $thread->forumid = $request->forumid;
        $thread->save();

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
        $this->authorizeAction($thread);

        $forumId = $thread->forumid;

        // حذف الردود ثم المرفقات المرتبطة (يتم ذلك عادة عن طريق cascaded deletes أو يدوياً)
        Post::where('threadid', $thread->threadid)->delete();
        $thread->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الموضوع بنجاح!',
            'redirect' => route('forum.show', ['id' => $forumId])
        ]);
    }
}
