<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        // حفظ آخر مكان أراد المستخدم العودة إليه بعد الدخول (مع التحقق من كونه رابطاً محلياً)
        $redirect = $request->input('redirect');
        if ($redirect && $this->isSafeLocalUrl($redirect)) {
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.login');
    }

    /**
     * التحقق من أن رابط العودة محلي (نفس النطاق) لمنع فتح redirect خارجي.
     */
    private function isSafeLocalUrl(string $url): bool
    {
        $parsed = parse_url($url);
        if (!$parsed || !in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            return false;
        }

        return ($parsed['host'] ?? '') === request()->getHost();
    }

    /**
     * التسجيل مغلق حالياً — تحويل لصفحة الدخول مع رسالة.
     * (دالة Controller بدلاً من closure حتى يبقى route:cache آمناً)
     */
    public function registerClosed()
    {
        return redirect()->route('login')->with('error', 'التسجيل مغلق حالياً');
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

            // تحديث آخر زيارة — lastvisit عند تسجيل الدخول + lastactivity للنشاط الحالي
            $user->update(['lastvisit' => time(), 'lastactivity' => time()]);

            // إن كان الرابط يحمل معامل redirect (من زر دخول في أي صفحة) فاحفظه ليعود إليه بعد الدخول
            $redirect = $request->input('redirect');
            if ($redirect && $this->isSafeLocalUrl($redirect)) {
                $request->session()->put('url.intended', $redirect);
            }

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
