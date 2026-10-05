<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeratorLog extends Model
{
    protected $table = 'moderatorlog';
    protected $primaryKey = 'moderatorlogid';
    public $timestamps = false;
    public $incrementing = true;

    protected $casts = [
        'moderatorlogid' => 'integer',
        'userid' => 'integer',
        'forumid' => 'integer',
        'threadid' => 'integer',
        'postid' => 'integer',
        'dateline' => 'integer',
    ];
}
