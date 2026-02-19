<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Helpers\BBCodeParser;

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

    /**
     * ترتيب زمني
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('dateline', 'asc');
    }

    /**
     * تحويل BBCode إلى HTML
     */
    public function getParsedContentAttribute(): string
    {
        return BBCodeParser::parse($this->pagetext ?? '');
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
