<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Thread;

class UserController extends Controller
{
    /**
     * عرض ملف العضو الشخصي
     */
    public function show(int $id)
    {
        $user = User::findOrFail($id);

        $threads = Thread::where('postuserid', $id)
            ->visible()
            ->orderBy('dateline', 'desc')
            ->with('forum')
            ->paginate(15);

        return view('user.show', compact('user', 'threads'));
    }
}
