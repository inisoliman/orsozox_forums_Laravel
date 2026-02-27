<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use App\Http\Requests\ThreadEditRequest;
use App\Helpers\HtmlSanitizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ThreadEditController extends Controller
{
    use AuthorizesRequests;

    /**
     * حفظ تعديلات الموضوع (العنوان والرد الأول) باستخدام المحرر الجديد
     */
    public function update(ThreadEditRequest $request, int $id)
    {
        $thread = Thread::with('firstPost')->findOrFail($id);

        // التحقق من الصلاحيات عبر ThreadPolicy
        $this->authorize('update', $thread);

        // تنظيف العنوان والمحتوى
        $title = strip_tags($request->title);
        $pagetext = HtmlSanitizer::clean($request->pagetext);

        // إضافة علامة للتفريق بين BBCode القديم والمحتوى بصيغة HTML
        $finalPagetext = '<!-- HTML -->' . $pagetext;

        // تحديث العنوان
        $thread->title = $title;
        $thread->save();

        // تحديث المحتوى للرد الأول
        if ($thread->firstPost) {
            $thread->firstPost->update(['pagetext' => $finalPagetext]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ تعديلات الموضوع بنجاح.',
            'title' => $title,
            'content' => $pagetext // نرسل HTML النظيف للعرض المباشر
        ]);
    }
}
