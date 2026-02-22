<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsTicker extends Model
{
    protected $table = 'news_tickers';

    protected $fillable = [
        'content',
        'url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
