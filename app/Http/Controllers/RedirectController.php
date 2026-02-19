<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\Forum;
use App\Models\User;

class RedirectController extends Controller
{
    /**
     * تحويل رابط showthread.php القديم
     * showthread.php?t=123 → /thread/123/slug
     */
    public function showThread(Request $request)
    {
        $id = $request->input('t') ?? $request->input('p');
        if (!$id)
            abort(404);

        $thread = Thread::find($id);
        if (!$thread)
            abort(404);

        return redirect($thread->url, 301);
    }

    /**
     * تحويل رابط forumdisplay.php القديم
     * forumdisplay.php?f=5 → /forum/5/slug
     */
    public function forumDisplay(Request $request)
    {
        $id = $request->input('f');
        if (!$id)
            abort(404);

        $forum = Forum::find($id);
        if (!$forum)
            abort(404);

        return redirect($forum->url, 301);
    }

    /**
     * تحويل رابط member.php القديم
     * member.php?u=10 → /user/10
     */
    public function member(Request $request)
    {
        $id = $request->input('u');
        if (!$id)
            abort(404);

        $user = User::find($id);
        if (!$user)
            abort(404);

        return redirect($user->url, 301);
    }
}
