<?php

use Illuminate\Support\Facades\Artisan;

// التحقق من وجود الملفات المطلوبة
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// تهيئة النواة
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// تنظيف الكاش (Cache) بشكل كامل
Artisan::call('optimize:clear');
Artisan::call('route:clear');
Artisan::call('config:clear');
Artisan::call('view:clear');

echo "<h2>✅ تم تنظيف الكاش (Cache) بنجاح!</h2>";
echo "<p>بما أنك قمت برفع التعديلات مؤخراً، كان يجب تفريغ الكاش المخبأ حتى يرى الخادم التغييرات.</p>";
echo "<p>يرجى التوجه إلى <a href='/forums/admin'>لوحة التحكم /admin</a> وتجربة الدخول الآن.</p>";
echo "<hr><p><b>ملاحظة هامة:</b> بعد الانتهاء، يرجى حذف هذا الملف <code>cc.php</code> من باب الحماية.</p>";
