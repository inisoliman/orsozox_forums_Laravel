<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ThreadApiController extends Controller
{
    /**
     * GET /api/threads — قائمة المواضيع
     */
    public function index(Request $request): JsonResponse
    {
        $threads = Thread::visible()
            ->orderBy('dateline', 'desc')
            ->with(['forum:forumid,title', 'author:userid,username'])
            ->when($request->input('forum_id'), function ($q, $forumId) {
                $q->where('forumid', $forumId);
            })
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $threads,
        ]);
    }

    /**
     * GET /api/threads/{id} — موضوع واحد مع الردود
     */
    public function show(int $id): JsonResponse
    {
        $thread = Thread::with([
            'forum:forumid,title',
            'author:userid,username',
            'posts' => function ($q) {
                $q->visible()->chronological()->with('author:userid,username');
            },
        ])->visible()->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $thread,
        ]);
    }
}
