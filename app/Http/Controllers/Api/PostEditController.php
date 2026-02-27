<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Http\Requests\PostEditRequest;
use App\Helpers\HtmlSanitizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PostEditController extends Controller
{
    use AuthorizesRequests;

    /**
     * حفظ تعديلات الرد باستخدام المحرر الجديد
     */
    public function update(PostEditRequest $request, int $id)
    {
        $post = Post::findOrFail($id);

        // التحقق من الصلاحيات عبر PostPolicy
        $this->authorize('update', $post);

        // تنظيف المحتوى
        $pagetext = HtmlSanitizer::clean($request->pagetext);

        // إضافة علامة التفريق عن BBCode
        $finalPagetext = '<!-- HTML -->' . $pagetext;

        // تحديث المحتوى
        $post->update(['pagetext' => $finalPagetext]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ تعديلات الرد بنجاح.',
            'content' => $pagetext
        ]);
    }
}
