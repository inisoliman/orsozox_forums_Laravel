<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Services\ModerationActionService;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModerationBulkController extends Controller
{
    public function __construct(
        private readonly ModerationActionService $actions,
        private readonly ModerationService $moderation,
    ) {
    }

    public function move(Request $request): JsonResponse
    {
        $data = $request->validate([
            'thread_ids' => ['required', 'array', 'min:1', 'max:100'],
            'thread_ids.*' => ['integer', 'distinct', 'exists:thread,threadid'],
            'forumid' => ['required', 'integer', 'exists:forum,forumid'],
        ]);
        DB::transaction(function () use ($data, $request) {
            $threads = Thread::whereIn('threadid', $data['thread_ids'])->lockForUpdate()->get();
            if ($threads->count() !== count($data['thread_ids'])) {
                throw new \RuntimeException('تعذر العثور على جميع المواضيع.');
            }
            foreach ($threads as $thread) {
                $this->actions->moveThread($thread, (int) $data['forumid'], $request->user(), true);
            }
        });
        return response()->json(['success' => true, 'message' => 'تم نقل المواضيع المحددة.']);
    }

    public function action(Request $request): JsonResponse
    {
        $data = $request->validate([
            'thread_ids' => ['required', 'array', 'min:1', 'max:100'],
            'thread_ids.*' => ['integer', 'distinct', 'exists:thread,threadid'],
            'action' => ['required', 'in:approve,soft_delete,restore,stick,unstick,open,close'],
            'reason' => ['nullable', 'string', 'max:125'],
        ]);
        DB::transaction(function () use ($data, $request) {
            $threads = Thread::whereIn('threadid', $data['thread_ids'])->lockForUpdate()->get();
            if ($threads->count() !== count($data['thread_ids'])) {
                throw new \RuntimeException('تعذر العثور على جميع المواضيع.');
            }
            foreach ($threads as $thread) {
                match ($data['action']) {
                    'approve' => (int) $thread->visible === 0 ? (bool) $this->moderation->approveThread($thread->threadid) : null,
                    'soft_delete' => $this->actions->softDeleteThread($thread, $request->user(), (string) ($data['reason'] ?? '')),
                    'restore' => $this->actions->restoreThread($thread, $request->user()),
                    'stick' => $this->actions->setSticky($thread, true, $request->user()),
                    'unstick' => $this->actions->setSticky($thread, false, $request->user()),
                    'open' => $this->actions->setOpen($thread, true, $request->user()),
                    'close' => $this->actions->setOpen($thread, false, $request->user()),
                };
            }
        });
        return response()->json(['success' => true, 'message' => 'تم تنفيذ العملية على المواضيع المحددة.']);
    }

    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_thread_id' => ['required', 'integer', 'exists:thread,threadid'],
            'source_thread_ids' => ['required', 'array', 'min:1', 'max:20'],
            'source_thread_ids.*' => ['integer', 'distinct', 'exists:thread,threadid'],
        ]);
        $target = Thread::findOrFail($data['target_thread_id']);
        $this->actions->mergeThreads($target, $data['source_thread_ids'], $request->user());
        return response()->json(['success' => true, 'message' => 'تم دمج المواضيع.', 'redirect' => $target->url]);
    }

    public function movePosts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_ids' => ['required', 'array', 'min:1', 'max:100'],
            'post_ids.*' => ['integer', 'distinct', 'exists:post,postid'],
            'target_thread_id' => ['required', 'integer', 'exists:thread,threadid'],
        ]);
        $target = Thread::findOrFail($data['target_thread_id']);
        $this->actions->movePosts($data['post_ids'], $target, $request->user());
        return response()->json(['success' => true, 'message' => 'تم نقل الردود.', 'redirect' => $target->url]);
    }
}
