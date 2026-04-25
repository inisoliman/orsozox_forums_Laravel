<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SystemCleanup extends Command
{
    protected $signature = 'system:cleanup';
    protected $description = 'تنظيف تلقائي آمن للكاش والجلسات والملفات المؤقتة — مصمم لاستضافة Hostinger';

    public function handle()
    {
        $this->info('⏳ بدء التنظيف...');

        // 1. تنظيف الكاش
        $this->callSilent('cache:clear');
        $this->callSilent('config:clear');
        $this->callSilent('view:clear');
        $this->info('✅ تم تنظيف الكاش');

        // 2. تنظيف ملفات الـ Sessions القديمة (أقدم من 7 أيام)
        $sessionPath = storage_path('framework/sessions');
        $deleted = 0;
        if (File::exists($sessionPath)) {
            $now = time();
            $maxAge = 604800; // 7 أيام
            foreach (File::files($sessionPath) as $file) {
                if ($now - $file->getMTime() >= $maxAge) {
                    File::delete($file);
                    $deleted++;
                }
            }
        }
        $this->info("✅ تم حذف {$deleted} ملف جلسة قديم");

        // 3. حذف الملفات المؤقتة من storage/app
        $tmpCleared = 0;
        $tmpPath = storage_path('app');
        if (File::exists($tmpPath)) {
            foreach (File::files($tmpPath) as $file) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['tmp', 'temp', 'log'])) {
                    File::delete($file);
                    $tmpCleared++;
                }
            }
        }
        $this->info("✅ تم حذف {$tmpCleared} ملف مؤقت");

        // 4. تنظيف ملفات logs القديمة (أقدم من 7 أيام) — للحماية الإضافية
        $logPath = storage_path('logs');
        $logsDeleted = 0;
        if (File::exists($logPath)) {
            $now = time();
            foreach (File::files($logPath) as $file) {
                // لا تحذف الملف الحالي (اليوم)
                if ($file->getFilename() === '.gitignore') continue;
                if ($now - $file->getMTime() >= 604800) {
                    File::delete($file);
                    $logsDeleted++;
                }
            }
        }
        $this->info("✅ تم حذف {$logsDeleted} ملف سجل قديم");

        $this->info('🎉 اكتمل التنظيف بنجاح!');
        return 0;
    }
}
