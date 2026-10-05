<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    private const TTL_MINUTES = 60;

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        /* لا نكشف هل البريد موجود أم لا — نفس الرسالة دائماً */
        $genericMessage = 'إذا كان البريد مسجلاً لدينا فستصلك رسالة تحتوي رابط إعادة التعيين.';

        if ($user) {
            $token = bin2hex(random_bytes(32));
            Cache::put('pwreset_' . $token, $user->userid, now()->addMinutes(self::TTL_MINUTES));

            $link = route('password.reset', ['token' => $token]);

            try {
                Mail::raw(
                    "مرحباً {$user->username},\n\n"
                    . "تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بك.\n"
                    . "افتح الرابط التالي خلال " . self::TTL_MINUTES . " دقيقة:\n\n{$link}\n\n"
                    . "إن لم تطلب ذلك فتجاهل هذه الرسالة.",
                    function ($message) use ($user) {
                        $message->to($user->email)
                            ->subject('إعادة تعيين كلمة المرور');
                    }
                );
            } catch (\Throwable $e) {
                report($e);
                /* لا نكشف فشل الإرسال للزائر */
            }
        }

        return back()->with('status', $genericMessage);
    }

    public function showResetForm(string $token)
    {
        if (! Cache::has('pwreset_' . $token)) {
            return redirect()->route('login')->with('error', 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.');
        }

        return view('auth.reset-password', ['token' => $token]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
        ]);

        $key = 'pwreset_' . $data['token'];
        $userid = Cache::get($key);

        if (! $userid) {
            return back()->with('error', 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.');
        }

        $user = User::find($userid);
        if (! $user) {
            Cache::forget($key);
            return back()->with('error', 'تعذر العثور على الحساب.');
        }

        /* صيغة vBulletin: md5(md5(password) + salt) */
        $salt = $user->salt ?: substr(md5((string) mt_rand()), 0, 30);
        $user->update([
            'password' => md5(md5($data['password']) . $salt),
            'salt' => $salt,
        ]);

        /* Token يُستهلك مرة واحدة فقط */
        Cache::forget($key);

        return redirect()->route('login')->with('success', 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.');
    }
}
