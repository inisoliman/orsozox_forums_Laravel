<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $table = 'attachment';
    protected $primaryKey = 'attachmentid';
    public $timestamps = false;

    protected $fillable = [
        'postid',
        'userid',
        'filename',
        'filedata',
        'dateline',
        'visible',
        'counter',
        'filehash',
        'extension',
    ];

    protected $casts = [
        'attachmentid' => 'integer',
        'postid' => 'integer',
        'userid' => 'integer',
        'dateline' => 'integer',
        'visible' => 'integer',
        'counter' => 'integer',
    ];

    /**
     * المشاركة التي ينتمي إليها المرفق
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'postid', 'postid');
    }

    /**
     * المستخدم الذي رفع المرفق
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    /**
     * هل المرفق صورة؟
     */
    public function getIsImageAttribute(): bool
    {
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        return in_array(strtolower($this->extension ?? ''), $imageExts);
    }

    /**
     * تاريخ الرفع
     */
    public function getCreatedDateAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromTimestamp($this->dateline);
    }
}
