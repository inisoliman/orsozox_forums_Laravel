<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'session';
    protected $primaryKey = 'sessionhash';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'sessionhash',
        'userid',
        'host',
        'idhash',
        'lastactivity',
        'location',
        'useragent',
        'loggedin',
        'badlocation',
        'bypass'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    public function getIpAddressAttribute()
    {
        return $this->host;
    }

    public function getUserAgentAttribute($value)
    {
        return $value;
    }

    public function getLastActivityAttribute($value)
    {
        return \Carbon\Carbon::createFromTimestamp($value);
    }
}
