# 🚀 أوامر جاهزة بالمسار الصحيح — انسخ والصق

> المسار الفعلي لمشروعك:
> `/home/u626751827/domains/orsozox.com/public_html/forums`

---

## 🛑 إصلاح خطأ 500 بعد رفع الملفات (2026-10-05)

ظهر خطأ 500 على كل الصفحات بعد رفع الملفات، وسببه **خطأ PHP قاتل (Fatal)**:

```
Cannot make non static method Illuminate\Routing\Controller::middleware() static
in class App\Http\Controllers\RegistrationController at .../RegistrationController.php:86
```

### السبب
في Laravel 11 الدالة `Controller::middleware()` في الكلاس الأب **غير ساكنة (non-static)**.
كانت ثلاثة كونترولرز تعرّفها كـ `public static function middleware()` فأصبح ذلك
تعارضاً في التوقيع يقتل التطبيق بالكامل (لأن `RegistrationController` يُحمَّل عبر المسارات).

### الإصلاح (في الكود — تم)
- حُذفت الدوال المكسورة `static middleware()` من:
  `RegistrationController` و `AccountController` و `PasswordResetController`.
- نُقلت الوسائط (middleware) إلى ملف `routes/web.php` (النمط المتّبع في المشروع):
  - `register.submit` ← throttle `10,10` (موجود مسبقاً).
  - `password.email` و `password.update` ← throttle `5,10` (موجود مسبقاً).
  - مسارات الحساب (`account.settings/password/email`) ← أُضيف `auth` (كان يأتي من الدالة المحذوفة).

### خطأ ثانٍ (كاش قديم): `Route [password.request] not defined`
هذا الخطأ يظهر فقط بسبب **كاش المسارات القديم** على الخادم. المسار مُعرّف فعلاً في
`routes/web.php` (`/forgot-password` ← `password.request`).

### أوامر النشر المطلوبة (بعد رفع الملفات)
```bash
cd /home/u626751827/domains/orsozox.com/public_html/forums

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
```
> 🚫 **لا تستخدم `route:cache`** (يكسر الموقع بسبب closures). `route:clear` فقط كافٍ.
> راجع القسم 2️⃣ بالأسفل لتفاصيل أوامر الكاش.

### الملفات المعدّلة في هذا الإصلاح
- `app/Http/Controllers/RegistrationController.php`
- `app/Http/Controllers/AccountController.php`
- `app/Http/Controllers/PasswordResetController.php`
- `routes/web.php`

---

## 0️⃣ استكشاف بنية قاعدة البيانات (phpMyAdmin → SQL)

> قاعدة البيانات: `u626751827_for11`. هذه الأوامر تعمل على **كل** الجداول دفعة واحدة
> (الجدول، عدد الصفوف، الأعمدة، الأنواع، الافتراضات) — استخدمها قبل أي تعديل على كود
> يقرأ/يكتب جداول vBulletin القديمة لتجنّب أخطاء "Unknown column".

**0.1 — كل الجداول بعدد الصفوف والحجم:**
```sql
SELECT TABLE_NAME AS `table`, TABLE_ROWS AS `rows`, ENGINE,
       TABLE_COLLATION AS `collation`, ROUND((DATA_LENGTH+INDEX_LENGTH)/1024/1024,2) AS `size_MB`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'u626751827_for11'
ORDER BY TABLE_NAME;
```

**0.2 — بنية كل جدول (أعمدة + أنواع + افتراضات):**
```sql
SELECT TABLE_NAME AS `table`, COLUMN_NAME AS `column`, COLUMN_TYPE AS `type`,
       IS_NULLABLE AS `null`, COLUMN_DEFAULT AS `default`, COLUMN_KEY AS `key`, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'u626751827_for11'
ORDER BY TABLE_NAME, ORDINAL_POSITION;
```

**0.3 — بنية مختصرة (صف واحد لكل جدول — أسهل للنسخ في التوثيق):**
```sql
SELECT TABLE_NAME AS `table`,
  GROUP_CONCAT(CONCAT(COLUMN_NAME, ' ', COLUMN_TYPE,
    IF(IS_NULLABLE='NO',' NOT NULL',''),
    IF(COLUMN_KEY='PRI',' [PK]',''),
    IF(EXTRA='auto_increment',' [AI]',''),
    IF(COLUMN_DEFAULT IS NOT NULL, CONCAT(' DEFAULT=', COLUMN_DEFAULT),''))
    ORDER BY ORDINAL_POSITION SEPARATOR ', ') AS `structure`
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'u626751827_for11'
GROUP BY TABLE_NAME
ORDER BY TABLE_NAME;
```

> ⚠️ **مهم:** استخدم اسم القاعدة **حرفياً** `'u626751827_for11'` وليس `DATABASE()` —
> في phpMyAdmin قد يُنفَّذ الاستعلام بسياق `information_schema` فتُرجِع `DATABASE()`
> الجداول النظامية فقط بدل جداول المنتدى (خطأ شائع).

> 🎯 القاعدة الذهبية: أي `NOT NULL` **بلا** `DEFAULT` في عمود يجب أن يضبطه الكود صراحةً
> عند كل INSERT (راجع بنية جدول `thread` الفعلية في مستند المعمارية §10).

---

## 1️⃣ ملف .env
انسخ محتوى الملف `docs/env-الصحيح-CORRECT.env.txt` كاملاً وضعه في `.env`.
**أهم تغييرين:**
- `QUEUE_CONNECTION=database` (سطر واحد فقط — كان مكرراً).
- إعداد بريد Hostinger (غيّر `MAIL_PASSWORD`).

---

## 2️⃣ بعد رفع الملفات — أعد بناء الكاش (Terminal / SSH)

### ⚠️ أولاً حل خطأ "Failed to clear cache"
هذا الخطأ يعني أن مجلدات الكاش ناقصة أو صلاحياتها خاطئة. نفّذ هذه الأوامر **بالترتيب**:
```bash
cd /home/u626751827/domains/orsozox.com/public_html/forums

# 1) أنشئ المجلدات المطلوبة إن كانت ناقصة
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# 2) اضبط الصلاحيات (مهم على الاستضافة المشتركة)
chmod -R 775 storage bootstrap/cache

# 3) (طريقة بديلة آمنة لمسح الكاش إن استمر الخطأ) احذف الملفات يدوياً
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*
```

### ثانياً أعد بناء الكاش
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
```
> 🚫 **لا تستخدم `php artisan route:cache` أبداً في هذا المشروع!**
> ملف `routes/web.php` يحتوي على closures (دوال مجهولة في `/register` و `/f{id}`...)،
> و`route:cache` معها يسبب **خطأ 405 Method Not Allowed** على الصفحة الرئيسية.
> اكتفِ بـ `config:cache` فقط — هو الأهم للأداء.

> ⚠️ مهم جداً: أمر `view:clear` يحذف القوالب المُجمَّعة القديمة (الخاطئة) لصفحة online-users،
> وهذا يُكمل حل خطأ 500. لو فشل `cache:clear` بعد ضبط الصلاحيات، استخدم `rm -rf` بالأعلى — يؤدي نفس الغرض.

> ملاحظة: لو ظهر "Failed to clear cache" فقط مع `cache:clear` فهذا **غير مؤثّر** طالما حذفت الملفات بـ `rm -rf`. الأهم هو نجاح `view:clear` و `config:cache`.

### 🚨 إن ظهر خطأ 405 Method Not Allowed على الموقع
هذا بسبب كاش راوتس معطوب من `route:cache`. الحل الفوري:
```bash
cd /home/u626751827/domains/orsozox.com/public_html/forums
php artisan route:clear
rm -f bootstrap/cache/routes-v7.php
```
ثم افتح الموقع — سيعمل فوراً. **ولا تشغّل `route:cache` بعدها.**



---

## 3️⃣ صلاحية سكريبت التنظيف (مرة واحدة)
```bash
chmod +x /home/u626751827/domains/orsozox.com/public_html/forums/scripts/hostinger-cache-cleanup.sh
```

---

## 4️⃣ تنظيف فوري للكاش المتراكم الآن
```bash
bash /home/u626751827/domains/orsozox.com/public_html/forums/scripts/hostinger-cache-cleanup.sh
```

---

## 5️⃣ الكرون جوبس (hPanel → Advanced → Cron Jobs)

### 🔎 أولاً: اكتشف مسار php الصحيح (مرة واحدة)
نفّذ في Terminal:
```bash
which php
php -v
```
- `which php` يطبع المسار الكامل (مثل `/usr/bin/php` أو `/opt/alt/php82/usr/bin/php`).
- استخدم الناتج في أوامر الكرون بالأسفل. **إن لم تعرفه، استخدم `php` فقط** (Hostinger غالباً يفهمها).

> 💡 الأبسط والأكثر أماناً: استخدم كلمة `php` المجردة في الكرون.
> Hostinger في معظم الخطط يفهم `php` تلقائياً.

### كرون (أ) — جدولة Laravel الأساسية (إلزامي) — كل دقيقة
**Schedule:** `* * * * *`
**Command (النسخة الموصى بها — php مجردة):**
```
cd /home/u626751827/domains/orsozox.com/public_html/forums && php artisan schedule:run >> /dev/null 2>&1
```
**أو (إن لم تعمل، استبدل php بالمسار من `which php`):**
```
cd /home/u626751827/domains/orsozox.com/public_html/forums && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```
> هذا يشغّل: إرسال الإيميلات (queue:work) + views:flush + إيميلات أعياد الميلاد + غيرها.

### كرون (ب) — تنظيف الكاش (إلزامي لمشكلة التضخم) — كل ساعة
**Schedule:** `0 * * * *`
**Command:**
```
/bin/bash /home/u626751827/domains/orsozox.com/public_html/forums/scripts/hostinger-cache-cleanup.sh >> /home/u626751827/domains/orsozox.com/public_html/forums/storage/logs/cache-cleanup.log 2>&1
```
> سكريبت التنظيف (ب) لا يحتاج php إطلاقاً — يعمل بـ bash فقط، فهو مضمون.

### كرون (ج) — احتياطي بديل عن (أ) لإرسال الإيميلات مباشرةً (اختياري)
إن لم تنجح `schedule:run` لأي سبب، استخدم هذا بدلاً منها — كل دقيقة:
```
cd /home/u626751827/domains/orsozox.com/public_html/forums && php artisan queue:work --queue=emails,default --stop-when-empty --max-time=55 >> /dev/null 2>&1
```


---

## 6️⃣ اختبار البريد (بعد ضبط .env)
```bash
cd /home/u626751827/domains/orsozox.com/public_html/forums
php artisan tinker
```
ثم داخل tinker:
```php
Mail::raw('اختبار البريد', fn($m) => $m->to('بريدك@gmail.com')->subject('Test'));
```

---

## 7️⃣ التأكد أن الكرون يعمل فعلاً (طريقة قاطعة)

### الطريقة 1 — اختبار سريع بكرون تجريبي (الأفضل للتأكد)
أضف كرون مؤقتاً يكتب الوقت في ملف كل دقيقة:
```
* * * * * date >> /home/u626751827/domains/orsozox.com/public_html/forums/storage/logs/cron-test.log 2>&1
```
انتظر دقيقتين ثم افحص:
```bash
cat /home/u626751827/domains/orsozox.com/public_html/forums/storage/logs/cron-test.log
```
- إن ظهرت أسطر تواريخ متتابعة → **الكرون يعمل** ✅ (احذف هذا الكرون التجريبي بعدها).
- إن بقي الملف فارغاً/غير موجود → الكرون لا يعمل (راجع إعداده في hPanel).

### الطريقة 2 — التأكد أن php والجدولة تعملان
```bash
cd /home/u626751827/domains/orsozox.com/public_html/forums

# هل php يعمل؟
php -v

# قائمة المهام المجدولة (يجب أن ترى قائمة بها queue:work و views:flush ...)
php artisan schedule:list

# شغّل الجدولة يدوياً مرة للتأكد أنها لا تعطي خطأ
php artisan schedule:run
```

### الطريقة 3 — فحص اللوجات (بعد عمل الكرون فترة)
```bash
# لوج تنظيف الكاش (يتحدّث كل ساعة)
cat storage/logs/cache-cleanup.log

# لوج إرسال الإيميلات (يتحدّث كل دقيقة عند وجود إيميلات)
cat storage/logs/queue-emails.log

# تأكد أن حجم مجلد الكاش لم يعد يتضخم
du -sh storage/framework/cache/ storage/framework/sessions/
```

> ✅ الخلاصة: استخدم **الطريقة 1** أولاً للتأكد 100% أن الكرون نفسه يعمل بمسار php الصحيح،
> ثم استخدم الكرون الحقيقي (schedule:run). بهذا تتأكد بدون الحاجة لمعرفة `/opt/alt/php82/...` مسبقاً.


---

## ✅ ملخص الترتيب
1. عدّل `.env` (queue سطر واحد + بريد Hostinger).
2. ارفع الملفات المعدّلة.
3. `view:clear` + إعادة بناء الكاش (خطوة 2).
4. `chmod +x` للسكريبت (خطوة 3).
5. تنظيف فوري (خطوة 4).
6. أضف الكرونين (أ) و (ب) (خطوة 5).
7. اختبر البريد + افتح `/forums/online-users` (يجب أن تعمل بلا 500).

> بعد ذلك: لا كاش متضخم، الإيميلات تصل، وصفحة المتواجدون تعمل وتُظهر البوتات. 🎉
