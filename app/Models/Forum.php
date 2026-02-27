<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Forum extends Model
{
    protected $table = 'forum';
    protected $primaryKey = 'forumid';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'description',
        'displayorder',
        'parentid',
    ];

    protected $casts = [
        'forumid' => 'integer',
        'parentid' => 'integer',
        'displayorder' => 'integer',
        'options' => 'integer',
        'threadcount' => 'integer',
        'replycount' => 'integer',
    ];

    /**
     * Accessor to strip HTML tags from title
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value ? strip_tags($value) : '',
        );
    }

    /**
     * المواضيع في هذا القسم
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class, 'forumid', 'forumid');
    }

    /**
     * القسم الأب
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'parentid', 'forumid');
    }

    /**
     * الأقسام الفرعية
     */
    public function children(): HasMany
    {
        return $this->hasMany(Forum::class, 'parentid', 'forumid');
    }

    /**
     * الأقسام النشطة فقط
     */
    public function scopeActive($query)
    {
        // vBulletin 3.8 stores active state in the options bitfield (Bit 1)
        return $query->whereRaw('options & 1');
    }

    /**
     * الأقسام الرئيسية (ليس لها قسم أب)
     */
    public function scopeRoot($query)
    {
        return $query->where(function ($q) {
            $q->where('parentid', 0)
                ->orWhere('parentid', -1)
                ->orWhereNull('parentid');
        });
    }

    /**
     * ترتيب حسب displayorder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('displayorder', 'asc');
    }

    /**
     * إنشاء slug من العنوان
     */
    public function getSlugAttribute(): string
    {
        return $this->createSlug($this->title);
    }

    public function getUrlAttribute(): string
    {
        $params = ['id' => $this->forumid];
        $slug = $this->slug;
        if (!empty($slug)) {
            $params['slug'] = $slug;
        }
        return route('forum.show', $params);
    }

    protected function createSlug(?string $text): string
    {
        if (empty($text))
            return 'forum';
        $text = trim($text);
        $text = preg_replace('/\s+/u', '-', $text);
        $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'forum';
    }
    /**
     * الصلاحيات الخاصة بهذا القسم
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(ForumPermission::class, 'forumid', 'forumid');
    }

    /**
     * الأقسام المتاحة للمستخدم الحالي (بناءً على forumpermission)
     *
     * المنطق: أخفِ القسم إذا وُجد سجل في forumpermission لمجموعة المستخدم
     * وكان Bit 1 (canview) غير مفعّل.
     * إذا لا يوجد سجل = مسموح (الإعداد الافتراضي في vBulletin)
     */
    public function scopeAccessible($query)
    {
        $user = auth()->user();
        $usergroupId = $user ? (int) $user->usergroupid : 1;

        // المشرفون والإدارة يرون كل الأقسام
        if (in_array($usergroupId, [5, 6, 7])) {
            return $query;
        }

        // أخفِ القسم فقط إذا كان لمجموعة المستخدم سجل صريح بالحجب (Bit 1 = 0)
        return $query->whereDoesntHave('permissions', function ($q) use ($usergroupId) {
            $q->where('usergroupid', $usergroupId)
                ->whereRaw('NOT (forumpermissions & 1)');
        });
    }

}
