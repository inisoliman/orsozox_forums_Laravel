<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.login');
    }

    /**
     * معالجة تسجيل الدخول
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'اسم المستخدم مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        // vBulletin 3.8 Login Logic
        $credentials = [
            'username' => $request->input('username'),
            'password' => $request->input('password'),
        ];

        $user = \App\Models\User::where('username', $credentials['username'])->first();

        if ($user && $user->verifyPassword($credentials['password'])) {
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->intended(route('home'))
                ->with('success', 'تم تسجيل الدخول بنجاح! مرحباً ' . $user->username);
        }

        return back()->withErrors([
            'username' => 'بيانات الدخول غير صحيحة. تأكد من اسم المستخدم وكلمة المرور.',
        ])->withInput($request->only('username'));
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')
            ->with('success', 'تم تسجيل الخروج بنجاح');
    }
}
