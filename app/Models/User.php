<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, Notifiable;

    protected $table = 'user';
    protected $primaryKey = 'userid';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'email',
        'password',
        'salt',
        'usergroupid',
        'joindate',
        'lastvisit',
        'lastactivity',
        'posts',
        'reputation',
        'title',
        'homepage',
        'usertitle',
        'avatarrevision',
    ];

    protected $hidden = [
        'password',
        'salt',
    ];

    protected $casts = [
        'userid' => 'integer',
        'usergroupid' => 'integer',
        'joindate' => 'integer',
        'lastvisit' => 'integer',
        'lastactivity' => 'integer',
        'posts' => 'integer',
        'reputation' => 'integer',
    ];

    /**
     * مواضيع العضو
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class, 'postuserid', 'userid');
    }

    /**
     * مشاركات العضو
     */
    public function userPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'userid', 'userid');
    }

    /**
     * تاريخ التسجيل
     */
    public function getJoinDateFormattedAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromTimestamp($this->joindate);
    }

    /**
     * آخر زيارة
     */
    public function getLastVisitFormattedAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromTimestamp($this->lastvisit ?? $this->lastactivity ?? time());
    }

    /**
     * رابط الملف الشخصي
     */
    public function getUrlAttribute(): string
    {
        return route('user.show', ['id' => $this->userid]);
    }

    /**
     * رابط الأفاتار — يمكن تعديله حسب مسار الصور في vBulletin
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatarrevision > 0) {
            return asset('customavatars/avatar' . $this->userid . '_' . $this->avatarrevision . '.gif');
        }
        return asset('images/default-avatar.png');
    }

    /**
     * هل العضو مشرف (مجموعة 5 أو 6 أو 7)
     */
    public function getIsAdminAttribute(): bool
    {
        return in_array($this->usergroupid, [5, 6, 7]);
    }

    /**
     * تحديد صلاحية الدخول للوحة تحكم Filament
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * اسم المستخدم لعرضه في Filament
     */
    public function getFilamentName(): string
    {
        return $this->username ?? 'مستخدم';
    }

    /**
     * هل العضو مشرف قسم (مجموعة 5 أو 6)
     */
    public function getIsModeratorAttribute(): bool
    {
        return in_array($this->usergroupid, [5, 6, 7]);
    }

    /**
     * التحقق من كلمة المرور بنظام vBulletin
     * vBulletin 3.8: md5(md5(password) + salt)
     */
    public function verifyPassword(string $password): bool
    {
        $hash = md5(md5($password) . $this->salt);
        return $hash === $this->password;
    }

    /**
     * اسم العضو للعرض - مطلوب لـ Laravel Auth
     */
    public function getAuthIdentifierName(): string
    {
        return 'userid';
    }

    public function getAuthIdentifier()
    {
        return $this->userid;
    }

    public function getAuthPassword(): string
    {
        return $this->password ?? '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // vBulletin لا يدعم remember token
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
