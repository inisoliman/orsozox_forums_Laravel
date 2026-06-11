<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// مسح جميع الكاش
Artisan::command('forum:clear-cache', function () {
    cache()->flush();
    $this->info('تم مسح جميع الكاش بنجاح!');
})->purpose('Clear all forum cache');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| NOTE: For any of these to run, the server MUST have a cron entry:
|
|   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
|
| Without that cron entry, scheduled commands will NEVER execute.
*/

// تنظيف تلقائي يومي الساعة 3 فجراً
// Flush cached thread views regularly before old cache files are cleaned.
Schedule::command('views:flush')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/views-flush.log'));

// Disabled: this runs cache:clear/config:clear/view:clear and is too aggressive for production traffic.
// Use scripts/hostinger-cache-cleanup.sh from hosting cron for safe old-file cleanup instead.
// Schedule::command('system:cleanup')->dailyAt('03:00');

// إرسال رسائل تهنئة عيد الميلاد يومياً الساعة 8 صباحاً
Schedule::command('email:birthday-direct')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/birthday-emails.log'));

// تشغيل عامل الطوابير للبريد باستمرار (صف "emails")
// يعمل لمدة دقيقة واحدة كل دقيقة، فيُرسل الرسائل المجدولة بلا توقف
Schedule::command('queue:work --queue=emails --stop-when-empty --tries=3 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-emails.log'));

// التحقق من المشتركين غير الموثقين (weekly)
Schedule::command('email:validate-subscribers')
    ->weeklyOn(1, '04:00') // Mondays 4am
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/validate-subscribers.log'));
