<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// مسح جميع الكاش
Artisan::command('forum:clear-cache', function () {
    cache()->flush();
    $this->info('تم مسح جميع الكاش بنجاح!');
})->purpose('Clear all forum cache');
