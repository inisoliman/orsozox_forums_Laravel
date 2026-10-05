<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeratorAssignment extends Model
{
    protected $table = 'moderator';
    protected $primaryKey = 'moderatorid';
    public $timestamps = false;

    protected $casts = [
        'moderatorid' => 'integer',
        'userid' => 'integer',
        'forumid' => 'integer',
        'permissions' => 'integer',
        'permissions2' => 'integer',
    ];
}
