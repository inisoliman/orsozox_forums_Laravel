<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    public static function isEnabled(): bool
    {
        return SiteSetting::getValue('registration.enabled', '1') === '1';
    }

    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        if (! self::isEnabled()) {
            return redirect()->route('login')->with('error', 'التسجيل مغلق حالياً.');
        }

        return view('auth.register');
    }

    public function store(Request $request)
    {
        if (! self::isEnabled()) {
            abort(403, 'التسجيل مغلق حالياً.');
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:100', 'unique:user,username'],
            'email' => ['required', 'email', 'max:100', 'unique:user,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'username.unique' => 'اسم العضوية مستخدم بالفعل.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => md5($data['password']),
            'salt' => '',
            'usergroupid' => 2,
            'joindate' => time(),
            'lastvisit' => time(),
            'lastactivity' => time(),
            'posts' => 0,
            'reputation' => 10,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'تم إنشاء حسابك بنجاح. مرحباً بك!');
    }

    /**
     * إنشاء عضو من لوحة الإدارة (يعمل حتى لو كان التسجيل العام مغلقاً).
     */
    public static function createUserByAdmin(array $data): User
    {
        $salt = substr(md5((string) mt_rand()), 0, 30);

        return User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => md5(md5($data['password']) . $salt),
            'salt' => $salt,
            'usergroupid' => (int) ($data['usergroupid'] ?? 2),
            'joindate' => time(),
            'lastvisit' => time(),
            'lastactivity' => time(),
            'posts' => 0,
            'reputation' => 10,
        ]);
    }
}
