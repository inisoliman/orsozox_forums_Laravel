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
        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);

        $threads = Thread::visible()
            ->orderBy('dateline', 'desc')
            ->with(['forum:forumid,title', 'author:userid,username'])
            ->when($request->input('forum_id'), function ($q, $forumId) {
                $q->where('forumid', $forumId);
            })
            // simplePaginate prevents expensive API COUNT(*) calls.
            ->simplePaginate($perPage);

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
                // Cap embedded posts to prevent one API request from loading huge threads.
                $q->visible()->chronological()->with('author:userid,username')->limit(50);
            },
        ])->visible()->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $thread,
        ]);
    }
}
