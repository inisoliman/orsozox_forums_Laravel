<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Thread;
use App\Models\VisitorMessage;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * موافقة / رفض المحتوى قيد المراجعة من داخل المنتدى (للأدمن والمشرف).
 */
class ModerationController extends Controller
{
    public function __construct(private readonly ModerationService $moderation)
    {
    }

    /**
     * تحقق أن المستخدم الحالي أدمن أو مشرف.
     */
    private function authorizeStaff(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->is_admin || $user->is_moderator), 403, 'ليس لديك صلاحية للمراجعة.');
    }

    /**
     * الموافقة على موضوع.
     */
    public function approveThread(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $ok = $this->moderation->approveThread($id);
        return response()->json(['success' => $ok, 'message' => $ok ? 'تمت الموافقة على الموضوع ونشره.' : 'لم يُعثر على الموضوع أو أنه منشور مسبقاً.']);
    }

    /**
     * الموافقة على رد.
     */
    public function approvePost(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $ok = $this->moderation->approvePost($id);
        return response()->json(['success' => $ok, 'message' => $ok ? 'تمت الموافقة على الرد ونشره.' : 'لم يُعثر على الرد أو أنه منشور مسبقاً.']);
    }

    /**
     * الموافقة على رسالة زائر.
     */
    public function approveVisitorMessage(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $ok = $this->moderation->approveVisitorMessage($id);
        return response()->json(['success' => $ok, 'message' => $ok ? 'تمت الموافقة على الرسالة ونشرها.' : 'لم تُعثر على الرسالة أو أنها منشورة مسبقاً.']);
    }

    /**
     * رفض/حذف موضوع قيد المراجعة (مع ردوده).
     */
    public function rejectThread(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $deleted = (bool) DB::transaction(function () use ($id) {
            $thread = Thread::whereKey($id)->where('visible', 0)->first();
            if (! $thread) {
                return false;
            }
            Post::where('threadid', $thread->threadid)->delete();
            return (bool) $thread->delete();
        });
        return response()->json(['success' => $deleted, 'message' => $deleted ? 'تم رفض الموضوع وحذفه.' : 'لم يُعثر على الموضوع.']);
    }

    /**
     * رفض/حذف رد قيد المراجعة.
     */
    public function rejectPost(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $deleted = (bool) Post::whereKey($id)->where('visible', 0)->delete();
        return response()->json(['success' => $deleted, 'message' => $deleted ? 'تم رفض الرد وحذفه.' : 'لم يُعثر على الرد.']);
    }

    /**
     * رفض/حذف رسالة زائر قيد المراجعة.
     */
    public function rejectVisitorMessage(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $deleted = (bool) VisitorMessage::whereKey($id)->where('state', 'moderation')->delete();
        return response()->json(['success' => $deleted, 'message' => $deleted ? 'تم رفض الرسالة وحذفها.' : 'لم تُعثر على الرسالة.']);
    }
}
