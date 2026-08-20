<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\BBCodeParser;
use Illuminate\Support\Facades\Cache;

class VisitorMessage extends Model
{
    protected $table = 'visitormessage';
    protected $primaryKey = 'vmid';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'userid',
        'postuserid',
        'postusername',
        'dateline',
        'state',
        'title',
        'pagetext',
    ];

    protected $casts = [
        'vmid' => 'integer',
        'userid' => 'integer',
        'postuserid' => 'integer',
        'dateline' => 'integer',
        'allowsmilie' => 'integer',
        'reportthreadid' => 'integer',
        'messageread' => 'integer',
    ];

    public function scopeVisible($query)
    {
        return $query->where('state', 'visible');
    }

    public function scopeModeration($query)
    {
        return $query->where('state', 'moderation');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'postuserid', 'userid');
    }

    public function profileOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    public function getCreatedDateAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromTimestamp($this->dateline);
    }

    public function getParsedContentAttribute(): string
    {
        $text = (string) ($this->pagetext ?? '');
        $cacheKey = 'visitormessage_parsed:' . $this->vmid . ':' . md5($text);

        return Cache::remember($cacheKey, 86400, function () use ($text) {
            if (str_starts_with($text, '<!-- HTML -->')) {
                return str_replace('<!-- HTML -->', '', $text);
            }
            return BBCodeParser::parse($text);
        });
    }
}