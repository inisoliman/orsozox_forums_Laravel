<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Thread;
use App\Models\VisitorMessage;
use App\Services\ModerationService;
use App\Services\ModerationActionService;
use App\Services\ModerationPermissionService;
use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * موافقة / رفض المحتوى قيد المراجعة من داخل المنتدى (للأدمن والمشرف).
 */
class ModerationController extends Controller
{
    public function __construct(
        private readonly ModerationService $moderation,
        private readonly ModerationActionService $actions,
        private readonly ModerationPermissionService $permissions,
    )
    {
    }

    /**
     * تحقق أن المستخدم الحالي أدمن أو مشرف.
     */
    private function authorizeStaff(): void
    {
        $user = auth()->user();
        abort_unless($user && $this->permissions->isModerator($user), 403, 'ليس لديك صلاحية للمراجعة.');
    }

    /**
     * الموافقة على موضوع.
     */
    public function approveThread(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $thread = Thread::findOrFail($id);
        abort_unless((int) $thread->visible === 0, 422, 'الموضوع ليس قيد المراجعة.');
        abort_unless($this->permissions->can($request->user(), 'moderate_post', $thread), 403, 'ليس لديك صلاحية مراجعة الموضوع.');
        $ok = $this->moderation->approveThread($id);
        return response()->json(['success' => $ok, 'message' => $ok ? 'تمت الموافقة على الموضوع ونشره.' : 'لم يُعثر على الموضوع أو أنه منشور مسبقاً.']);
    }

    /**
     * الموافقة على رد.
     */
    public function approvePost(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $post = Post::with('thread')->findOrFail($id);
        abort_unless((int) $post->visible === 0, 422, 'الرد ليس قيد المراجعة.');
        abort_unless($post->thread && (int) $post->thread->visible === 1, 422, 'لا يمكن نشر رد داخل موضوع غير منشور.');
        abort_unless($this->permissions->canModeratePost($request->user(), $post), 403, 'ليس لديك صلاحية مراجعة الرد.');
        $ok = $this->moderation->approvePost($id);
        return response()->json(['success' => $ok, 'message' => $ok ? 'تمت الموافقة على الرد ونشره.' : 'لم يُعثر على الرد أو أنه منشور مسبقاً.']);
    }

    /**
     * الموافقة على رسالة زائر.
     */
    public function approveVisitorMessage(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        abort_unless($this->permissions->canModerateVisitorMessage($request->user()), 403, 'ليس لديك صلاحية مراجعة رسالة الزائر.');
        $ok = $this->moderation->approveVisitorMessage($id);
        return response()->json(['success' => $ok, 'message' => $ok ? 'تمت الموافقة على الرسالة ونشرها.' : 'لم تُعثر على الرسالة أو أنها منشورة مسبقاً.']);
    }

    /**
     * رفض/حذف موضوع قيد المراجعة (مع ردوده).
     */
    public function rejectThread(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $thread = Thread::findOrFail($id);
        abort_unless((int) $thread->visible === 0, 422, 'الموضوع ليس قيد المراجعة.');
        abort_unless($this->permissions->canSoftDeleteThread($request->user(), $thread), 403, 'ليس لديك صلاحية رفض الموضوع.');
        try {
            $this->actions->softDeleteThread($thread, $request->user(), (string) $request->input('reason', 'رفض من المراجعة'));
            return response()->json(['success' => true, 'message' => 'تم رفض الموضوع ونقله إلى المحذوفات.']);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * رفض/حذف رد قيد المراجعة.
     */
    public function rejectPost(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        $post = Post::with('thread')->findOrFail($id);
        abort_unless((int) $post->visible === 0, 422, 'الرد ليس قيد المراجعة.');
        abort_unless($this->permissions->canModeratePost($request->user(), $post), 403, 'ليس لديك صلاحية رفض الرد.');
        try {
            $this->actions->softDeletePost($post, $request->user(), (string) $request->input('reason', 'رفض من المراجعة'));
            return response()->json(['success' => true, 'message' => 'تم رفض الرد ونقله إلى المحذوفات.']);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * رفض/حذف رسالة زائر قيد المراجعة.
     */
    public function rejectVisitorMessage(Request $request, int $id): JsonResponse
    {
        $this->authorizeStaff();
        abort_unless($this->permissions->canModerateVisitorMessage($request->user()), 403, 'ليس لديك صلاحية رفض رسالة الزائر.');
        $message = VisitorMessage::findOrFail($id);
        $deleted = (bool) VisitorMessage::whereKey($id)->where('state', 'moderation')->delete();
        return response()->json(['success' => $deleted, 'message' => $deleted ? 'تم رفض الرسالة وحذفها.' : 'لم تُعثر على الرسالة.']);
    }
}
