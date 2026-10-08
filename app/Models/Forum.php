<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\HasSlug;

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
        'threadcount',
        'replycount',
        'lastpost',
        'lastpostid',
        'lastposter',
        'lastthread',
        'lastthreadid',
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
     *
     * vBulletin يُرتّب الأقسام بـ displayorder ثم forumid. عند تساوي
     * displayorder (وهو شائع جداً) لا يكون ترتيب MySQL مضموناً، لذلك
     * نضيف forumid كمرتّب ثانوي لضمان ترتيب ثابت ومطابق للوحة التحكم.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('displayorder', 'asc')
            ->orderBy('forumid', 'asc');
    }

    /**
     * قائمة مسطّحة مرتّبة هرميّاً (قسم رئيسي ثم أبناؤه ثم أحفاده) — للقوائم المنسدلة.
     *
     * تعيد Collection من مصفوفات: ['forumid','title','depth','label']
     * ليتطابق ترتيب القوائم المنسدلة مع شجرة الصفحة الرئيسية.
     *
     * @param callable|null $filter دالة اختيارية تُرجع true لإدراج القسم (مثل فحص صلاحية الإنشاء)
     * @return \Illuminate\Support\Collection<int, array{forumid:int,title:string,depth:int,label:string}>
     */
    public static function flatOrderedTree(?callable $filter = null): \Illuminate\Support\Collection
    {
        $forums = static::active()
            ->ordered()
            ->get(['forumid', 'title', 'parentid', 'displayorder', 'options']);

        $byParent = [];
        foreach ($forums as $forum) {
            $parentId = (int) ($forum->parentid ?? 0);
            if ($parentId < 0) {
                $parentId = 0; // -1 تعني جذراً في vBulletin
            }
            $byParent[$parentId][] = $forum;
        }

        $flat = [];
        $visited = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$flat, &$visited, $byParent, $filter) {
            if (empty($byParent[$parentId])) {
                return;
            }
            foreach ($byParent[$parentId] as $forum) {
                if (isset($visited[$forum->forumid])) {
                    continue; // حماية من أي حلقة parentid تالفة
                }
                $visited[$forum->forumid] = true;

                if ($filter === null || $filter($forum)) {
                    $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
                    $flat[] = [
                        'forumid' => (int) $forum->forumid,
                        'title' => $forum->title,
                        'depth' => $depth,
                        'label' => $prefix . $forum->title,
                    ];
                }

                $walk((int) $forum->forumid, $depth + 1);
            }
        };

        $walk(0, 0);

        // أي قسم لم يُدرج (parentid يشير إلى قسم غير موجود) — نضيفه في النهاية.
        foreach ($forums as $forum) {
            if (isset($visited[$forum->forumid])) {
                continue;
            }
            $visited[$forum->forumid] = true;
            if ($filter === null || $filter($forum)) {
                $flat[] = [
                    'forumid' => (int) $forum->forumid,
                    'title' => $forum->title,
                    'depth' => 0,
                    'label' => $forum->title,
                ];
            }
        }

        return collect($flat);
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

    use HasSlug;
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
        if ($user && $user->is_moderator) {
            return $query;
        }

        // أخفِ القسم فقط إذا كان لمجموعة المستخدم سجل صريح بالحجب (Bit 1 = 0)
        return $query->accessibleToGroup($usergroupId);
    }

    public function scopePubliclyAccessible($query)
    {
        $guestGroupId = (int) config('forum.guest_usergroup_id', 1);

        return $query->accessibleToGroup($guestGroupId);
    }

    public function scopeAccessibleToGroup($query, int $usergroupId)
    {
        return $query->whereDoesntHave('permissions', function ($permissionQuery) use ($usergroupId) {
            $permissionQuery->where('usergroupid', $usergroupId)
                ->whereRaw('(forumpermissions & ?) = 0', [ForumPermission::CAN_VIEW]);
        });
    }

}
