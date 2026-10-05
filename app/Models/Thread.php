<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\HasSlug;

class Thread extends Model
{
    protected $table = 'thread';
    protected $primaryKey = 'threadid';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'forumid',
        'postusername',
        'postuserid',
        'dateline',
        'views',
        'replycount',
        'open',
        'visible',
        'lastpost',
        'lastposter',
        'sticky',
    ];

    // قيم افتراضية لأعمدة جدول thread الفعلية (نوعها كما في SHOW COLUMNS).
    // أعمدة NOT NULL بلا DEFAULT في القاعدة المهاجَرة (prefixid, lastposter,
    // similar, notes) يجب ضبطها صراحة عند كل INSERT.
    protected $attributes = [
        'firstpostid' => 0,
        'lastpostid' => 0,
        'pollid' => 0,
        'iconid' => 0,
        'prefixid' => '',
        'lastposter' => '',
        'votenum' => 0,
        'votetotal' => 0,
        'attach' => 0,
        'similar' => '',
        'notes' => '',
    ];

    protected $casts = [
        'threadid' => 'integer',
        'forumid' => 'integer',
        'postuserid' => 'integer',
        'firstpostid' => 'integer',
        'replycount' => 'integer',
        'views' => 'integer',
        'open' => 'integer',
        'dateline' => 'integer',
        'visible' => 'integer',
        'sticky' => 'integer',
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
     * القسم الذي ينتمي إليه الموضوع
     */
    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forumid', 'forumid');
    }

    /**
     * جميع المشاركات/الردود
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'threadid', 'threadid');
    }

    /**
     * كاتب الموضوع
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'postuserid', 'userid');
    }

    /**
     * أول مشاركة (المحتوى الأصلي)
     */
    public function firstPost(): HasOne
    {
        return $this->hasOne(Post::class, 'threadid', 'threadid')
            ->orderBy('dateline', 'asc');
    }

    /**
     * المواضيع المرئية فقط
     */
    public function scopeVisible($query)
    {
        return $query->where('visible', 1);
    }

    public function scopePublished($query)
    {
        return $query->where('visible', 1);
    }

    public function scopePending($query)
    {
        return $query->where('visible', 0);
    }

    public function scopeSoftDeleted($query)
    {
        return $query->where('visible', 2);
    }

    public function scopePubliclyIndexable($query)
    {
        return $query->visible()->whereHas('forum', function ($forumQuery) {
            $forumQuery->active()->publiclyAccessible();
        })->when(
            ($excluded = \App\Models\SiteSetting::excludedSitemapForumIds()) !== [],
            fn ($q) => $q->whereNotIn('forumid', $excluded)
        );
    }

    /**
     * المواضيع المفتوحة
     */
    public function scopeOpen($query)
    {
        return $query->where('open', 1);
    }

    /**
     * الأحدث أولاً
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('dateline', 'desc');
    }

    /**
     * الأكثر مشاهدة
     */
    public function scopeMostViewed($query)
    {
        return $query->orderBy('views', 'desc');
    }

    /**
     * إنشاء slug من العنوان العربي
     */
    public function getSlugAttribute(): string
    {
        return $this->createSlug($this->title);
    }

    /**
     * تاريخ الإنشاء (تحويل Unix timestamp)
     */
    public function getCreatedDateAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromTimestamp($this->dateline);
    }

    /**
     * تاريخ آخر رد
     */
    public function getLastPostDateAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromTimestamp($this->lastpost);
    }

    public function getUrlAttribute(): string
    {
        $params = ['id' => $this->threadid];
        $slug = $this->slug;
        if (!empty($slug)) {
            $params['slug'] = $slug;
        }
        return route('thread.show', $params);
    }

    /**
     * ملخص قصير للوصف
     */
    public function getExcerptAttribute(): string
    {
        $firstPost = $this->firstPost;
        if (!$firstPost)
            return '';

        $text = strip_tags($firstPost->parsed_content);
        $text = preg_replace('/\s+/u', ' ', $text);
        return mb_substr(trim($text), 0, 200, 'UTF-8') . '...';
    }

    use HasSlug;
}
