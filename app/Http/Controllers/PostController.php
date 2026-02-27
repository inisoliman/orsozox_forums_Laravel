<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Thread;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    /**
     * Direct link to a specific post.
     * Finds the post, calculates which page it's on, and redirects to the thread.
     *
     * GET /posts/{postid}
     * → 301 redirect to /thread/{id}/{slug}?page=X#post-{postid}
     *
     * @param int $postid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(int $postid)
    {
        // Find the post with its thread (essential columns only)
        $post = Post::select('postid', 'threadid', 'dateline')
            ->where('postid', $postid)
            ->visible()
            ->firstOrFail();

        $thread = Thread::select('threadid', 'title')
            ->where('threadid', $post->threadid)
            ->visible()
            ->firstOrFail();

        // Calculate the position of this post in the thread
        // We must sort by dateline then postid (vBulletin standard)
        $position = Post::where('threadid', $post->threadid)
            ->visible()
            ->where(function ($query) use ($post) {
                $query->where('dateline', '<', $post->dateline)
                    ->orWhere(function ($q) use ($post) {
                        $q->where('dateline', $post->dateline)
                            ->where('postid', '<=', $post->postid);
                    });
            })
            ->count();

        // Calculate page number (15 posts per page — same as ThreadController)
        $perPage = 15;
        $page = (int) ceil($position / $perPage);

        // Build the redirect URL
        $params = [
            'id' => $thread->threadid,
            'slug' => $thread->slug,
        ];

        // Append page parameter (only if not page 1)
        if ($page > 1) {
            $params['page'] = $page;
        }

        // Redirect with hash anchor (301 for SEO safety)
        return redirect()->to(route('thread.show', $params) . '#post-' . $post->postid, 301);
    }
}
