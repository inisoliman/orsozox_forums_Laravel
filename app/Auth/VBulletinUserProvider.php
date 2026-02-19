<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class VBulletinUserProvider implements UserProvider
{
    /**
     * استرجاع المستخدم بالـ ID
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        return User::find($identifier);
    }

    /**
     * استرجاع المستخدم بالـ Token (غير مدعوم في vBulletin)
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    /**
     * تحديث Remember Token (غير مدعوم)
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // vBulletin لا يدعم remember token
    }

    /**
     * استرجاع المستخدم ببيانات الدخول
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials['username']) && empty($credentials['email'])) {
            return null;
        }

        $query = User::query();

        if (!empty($credentials['username'])) {
            $query->where('username', $credentials['username']);
        } elseif (!empty($credentials['email'])) {
            $query->where('email', $credentials['email']);
        }

        return $query->first();
    }

    /**
     * التحقق من كلمة المرور بنظام vBulletin 3.8
     * الخوارزمية: md5(md5(password) + salt)
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (!isset($credentials['password'])) {
            return false;
        }

        /** @var User $user */
        return $user->verifyPassword($credentials['password']);
    }

    /**
     * إعادة تجزئة كلمة المرور (غير مطلوب لـ vBulletin)
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // لا نعيد تجزئة كلمات مرور vBulletin
    }
}
