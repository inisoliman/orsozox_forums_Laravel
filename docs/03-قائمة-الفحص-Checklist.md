# ✅ قائمة الفحص والتطبيق — Deployment Checklist

خطوات تطبيق الحلول على استضافة Hostinger واختبار النتائج.

---

## 🔧 0) قبل أي تغيير — نسخة احتياطية
- [ ] خذ نسخة من `.env`.
- [ ] خذ نسخة من قاعدة البيانات (من Hostinger → Databases → Export).
- [ ] خذ نسخة من ملفات: `OnlineUsersService.php`, `UpdateLegacySession.php`, `SitemapController.php`.

---

## 📧 1) إصلاح الإيميلات

- [ ] افتح `.env` واحذف السطر المكرر `QUEUE_CONNECTION=database`. اترك سطراً واحداً فقط:
  ```env
  QUEUE_CONNECTION=sync
  ```
- [ ] اضبط بريد الدومين (موصى به بدل Gmail):
  ```env
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.hostinger.com
  MAIL_PORT=465
  MAIL_USERNAME=admin@orsozox.com
  MAIL_PASSWORD=<كلمة مرور البريد>
  MAIL_ENCRYPTION=ssl
  MAIL_FROM_ADDRESS=admin@orsozox.com
  MAIL_FROM_NAME="منتديات أرثوذكس"
  ```
- [ ] أعد بناء الكاش (انظر القسم 5).
- [ ] اختبر:
  ```bash
  php artisan tinker
  >>> Mail::raw('Test', fn($m) => $m->to('your@gmail.com')->subject('Test'));
  ```
- [ ] جرّب إرسال حملة من لوحة Filament وتأكد من وصولها.

✔️ **النتيجة المتوقعة:** الإيميل يصل خلال ثوانٍ (وليس عالقاً في جدول `jobs`).

---

## 🗺️ 2) إصلاح السايت ماب

> ⚠️ **أولاً — ما تأكد منه بالفحص الحي (2026-08-19):** خريطة المنتدى نفسها **تعمل ممتازاً**
> (`https://orsozox.com/forums/sitemap.xml` → 200/XML، وخرائطها الفرعية 168+1000 رابطة كلها 200).
> **المشكلة في جذر الدومين**: `orsozox.com/sitemap.xml` هي خريطة **WordPress/Yoast** فيها 153 خريطة
> فرعية لمقالات WP **بلا أي محتوى منتدى**، لذلك تظهر "0 صفحات" في Search Console.
> لا حاجة لتغيير `APP_URL` أو كاش الخرائط — المشكلة خارج مشروع Laravel.

- [ ] في **Google Search Console** أضف الخريطة الصحيحة للمنتدى مباشرةً:
  ```
  https://orsozox.com/forums/sitemap.xml
  ```
- [ ] (اختياري) في لوحة WordPress/Yoast أدرج خريطة المنتدى داخل `sitemap_index.xml` أو اجعلها أول سطر في `robots.txt` الجذر.
- [ ] تحقق أن الخرائط الفرعية تفتح 200/XML (لا 429) — الفحص الحي أكد ذلك.
- [ ] انتظر 24-48 ساعة ثم أعد فحص تقرير "خرائط المواقع" في Search Console.

✔️ **النتيجة المتوقعة:** يقرأ جوجل مئات روابط المواضيع والأقسام من خريطة المنتدى الفعلية.

---

## 🐞 3) إصلاح خطأ 500 في online-users

- [ ] (للتشخيص) فعّل مؤقتاً `APP_DEBUG=true`، افتح الصفحة، انسخ الخطأ من `storage/logs/laravel.log`، ثم **أعد** `APP_DEBUG=false`.
- [ ] طبّق تعديلات `OnlineUsersService.php` (paginate + try/catch) — سنقوم بها في الكود.
- [ ] طبّق تعديل `online/index.blade.php` (links افتراضي).
- [ ] إن استخدمنا view ترقيم مخصص:
  ```bash
  php artisan vendor:publish --tag=laravel-pagination
  ```
- [ ] أعد فتح الصفحة بعد مسح الكاش.

✔️ **النتيجة المتوقعة:** الصفحة تفتح وتعرض الأعضاء/الزوار/البوتات بدون 500.

---

## 🤖 4) إظهار بوتات محركات البحث

- [ ] طبّق تعديل `UpdateLegacySession.php` (تسجيل البوتات بـ throttle أطول بدل تخطّيها).
- [ ] انتظر مرور بوتات حقيقية (أو اختبر بتغيير User-Agent إلى `Googlebot`).
- [ ] افتح `/online-users` وتأكد من ظهور صف للبوت وعدّاد "عناكب بحث" > 0.

✔️ **النتيجة المتوقعة:** ظهور Googlebot/Bingbot في الجدول والعدّاد.

---

## 🔁 5) أوامر إعادة بناء الكاش (مهمة بعد أي تعديل)
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
```
> ملاحظة Hostinger: إن لم يتوفر SSH، استخدم ملف `public/clear-cache.php` مؤقتاً ثم **احذفه**.

---

## 🔐 6) تأمين الإنتاج (مهم)
- [ ] احذف: `reset_password.php`, `check.php`, `check_debug.php`, `check_login.php`.
- [ ] احذف: `public/cc.php`, `public/clear-cache.php`, `public/debug_admin.php`, `public/test.php`, `public/bbcode_test.php`.
- [ ] تأكد `APP_DEBUG=false` و `APP_ENV=production`.

---

## 📌 ملخص الملفات التي ستُعدَّل في الكود
| الملف | التعديل |
|------|---------|
| `.env` | حذف `QUEUE_CONNECTION=database` المكرر + ضبط البريد |
| `config/mail.php` | إنشاؤه (منشور) |
| `config/queue.php` | إنشاؤه (منشور) |
| `app/Services/OnlineUsersService.php` | `paginate` + try/catch دفاعي |
| `resources/views/online/index.blade.php` | `links()` افتراضي |
| `app/Http/Middleware/UpdateLegacySession.php` | تسجيل البوتات بدل تخطّيها |

> بعد إكمال التطبيق، حدّث هذا الملف بوضع ✅ أمام كل خطوة منفّذة.

## حالة منظومة إشراف vBulletin

- [ ] تشغيل PHP lint واختبارات `Moderation` على بيئة اختبار.
- [ ] التحقق من schema الفعلي لـ`moderatorlog` و`deletionlog` والمرفقات.
- [ ] اختبار الحذف البسيط والاستعادة والدمج والنقل للحسابات الأربعة.
- [ ] مراجعة Cloudflare Auto-Minify بعد رفع نسخة الاختبار.
- [ ] اعتماد قائمة الملفات والنسخة الاحتياطية وrollback في `docs/08-دليل-اختبار-الإشراف.md` قبل الرفع.
