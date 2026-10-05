<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Helpers\BBCodeParser;
use Illuminate\Support\Facades\Cache;

class Post extends Model
{
    protected $table = 'post';
    protected $primaryKey = 'postid';
    public $timestamps = false;

    protected $fillable = [
        'threadid',
        'userid',
        'username',
        'parentid',
        'title',
        'pagetext',
        'dateline',
        'visible',
        'ipaddress',
    ];

    protected $casts = [
        'postid' => 'integer',
        'threadid' => 'integer',
        'userid' => 'integer',
        'parentid' => 'integer',
        'dateline' => 'integer',
        'visible' => 'integer',
    ];

    /**
     * الموضوع الذي تنتمي إليه المشاركة
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class, 'threadid', 'threadid');
    }

    /**
     * كاتب المشاركة
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    /**
     * المرفقات
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'postid', 'postid');
    }

    /**
     * المشاركات المرئية فقط
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

    /**
     * ترتيب زمني
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('dateline', 'asc');
    }

    /**
     * تحويل BBCode إلى HTML (أو إرجاع HTML النظيف إذا تم تعديله بالمحرر الجديد)
     */
    public function getParsedContentAttribute(): string
    {
        $text = (string) ($this->pagetext ?? '');
        $cacheKey = 'post_parsed_content:' . $this->postid . ':' . md5($text);

        // BBCode parsing + image proxy transformation is CPU-heavy on old forum posts.
        // Cache by post id and content hash so edits automatically produce a new cache key.
        return Cache::remember($cacheKey, 86400, function () use ($text) {
            if (str_starts_with($text, '<!-- HTML -->')) {
                $parsed = str_replace('<!-- HTML -->', '', $text);
                // محتوى المحرر الجديد (HTML) — نحوّل رموز الأيقونات القديمة فيه أيضاً
                $parsed = BBCodeParser::convertSmilies($parsed);
            } else {
                $parsed = BBCodeParser::parse($text);
            }

            // Apply YouTube Lite Auto-Embed
            $parsed = app(\App\Services\YouTubeLiteEmbedService::class)->transformContent($parsed);

            // Apply Image Proxy (LIIMS — broken image handling)
            $parsed = app(\App\Services\ImageProxyService::class)->transformContent($parsed);

            return $parsed;
        });
    }

    /**
     * تاريخ المشاركة
     */
    public function getCreatedDateAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromTimestamp($this->dateline);
    }

    /**
     * نص مختصر بدون HTML
     */
    public function getPlainTextAttribute(): string
    {
        $text = strip_tags($this->parsed_content);
        return preg_replace('/\s+/', ' ', trim($text));
    }
}
