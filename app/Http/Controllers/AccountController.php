<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function edit()
    {
        return view('account.settings', ['user' => Auth::user()]);
    }

    /**
     * تغيير كلمة المرور — يتطلب كلمة المرور الحالية.
     */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
        ]);

        $user = Auth::user();

        if (! $user->verifyPassword($data['current_password'])) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        /* صيغة vBulletin: md5(md5(password) + salt) */
        $salt = $user->salt ?: substr(md5((string) mt_rand()), 0, 30);
        $user->update([
            'password' => md5(md5($data['password']) . $salt),
            'salt' => $salt,
        ]);

        return back()->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }

    /**
     * تغيير البريد الإلكتروني — يتطلب كلمة المرور الحالية. اسم العضوية غير قابل للتغيير.
     */
    public function updateEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:100', 'unique:user,email,' . Auth::id() . ',userid'],
            'current_password' => ['required', 'string'],
        ], [
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
        ]);

        $user = Auth::user();

        if (! $user->verifyPassword($data['current_password'])) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        $user->update(['email' => $data['email']]);

        return back()->with('success', 'تم تحديث البريد الإلكتروني بنجاح.');
    }
}
