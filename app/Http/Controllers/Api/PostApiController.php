<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostApiController extends Controller
{
    /**
     * GET /api/posts/{threadId} — ردود موضوع معين
     */
    public function index(int $threadId, Request $request): JsonResponse
    {
        $posts = Post::where('threadid', $threadId)
            ->visible()
            ->chronological()
            ->with('author:userid,username')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $posts,
        ]);
    }
}
