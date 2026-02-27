<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class EditorUploadController extends Controller
{
    /**
     * رفع الصور مباشرة من المحرر CKEditor
     */
    public function uploadImage(Request $request)
    {
        // يجب أن يكون المستخدم مسجلاً ومسموح له برفع الصور
        if (!Auth::check()) {
            return response()->json([
                'error' => [
                    'message' => 'يجب تسجيل الدخول لرفع الصور.'
                ]
            ], 403);
        }

        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // الحد الأقصى 5 ميجا
        ]);

        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            // اسم الملف باستخدام الهاش للحماية
            $filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();

            // رفع الصورة إلى مجلد public/attachments/editor
            $file->move(public_path('attachments/editor'), $filename);

            // استجابة متوافقة مع CKEditor 5
            return response()->json([
                'url' => asset('attachments/editor/' . $filename)
            ]);
        }

        return response()->json([
            'error' => [
                'message' => 'فشل في رفع الصورة.'
            ]
        ], 400);
    }
}
