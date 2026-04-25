# 🚀 دليل تثبيت المنتدى على Hostinger — خطوة بخطوة
# Forum Installation Guide — Hostinger Shared Hosting

> المسار المستهدف: `public_html/forums/`
> الرابط النهائي: `https://yourdomain.com/forums/`

---

## 📋 المتطلبات
- استضافة Hostinger (Business أو Premium)
- PHP 8.2 أو أعلى
- MySQL 5.7 أو أعلى
- قاعدة بيانات vBulletin 3.8 موجودة على نفس السيرفر
- وصول SSH (Terminal) في Hostinger

---

## الخطوة 1: تجهيز الملفات على جهازك 🖥️

### 1.1 — تنظيم المجلد

المشروع الحالي موجود في مجلد `forums` على جهازك. تأكد أن الهيكل كالتالي:

```
forums/
├── app/
├── bootstrap/
├── config/
├── public/
│   ├── .htaccess
│   ├── index.php
│   ├── css/app.css
│   └── robots.txt
├── resources/
├── routes/
├── storage/
├── .env.example
├── .htaccess
├── artisan
└── composer.json
```

### 1.2 — اضغط المشروع كـ ZIP

اضغط مجلد `forums` بالكامل كملف ZIP واحد اسمه `forums.zip`.

---

## الخطوة 2: الدخول إلى لوحة تحكم Hostinger 🌐

1. اذهب إلى [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. سجّل الدخول بحسابك
3. اختر الموقع المطلوب من قائمة المواقع
4. ستظهر لك لوحة التحكم الرئيسية

---

## الخطوة 3: التأكد من إصدار PHP ⚙️

1. من لوحة التحكم، اذهب إلى: **Advanced** → **PHP Configuration**
2. تأكد أن الإصدار **PHP 8.2** أو أعلى
3. إذا كان أقل، غيّره إلى **8.2** واضغط **Update**
4. في نفس الصفحة، تأكد من تفعيل هذه الإضافات:
   - ✅ `mbstring`
   - ✅ `openssl`
   - ✅ `pdo_mysql`
   - ✅ `tokenizer`
   - ✅ `xml`
   - ✅ `ctype`
   - ✅ `json`
   - ✅ `fileinfo`
   - ✅ `gd` أو `imagick`

---

## الخطوة 4: رفع الملفات 📤

### الطريقة أ: عبر File Manager (سهلة)

1. من لوحة التحكم اذهب إلى: **Files** → **File Manager**
2. افتح مجلد `public_html`
3. اضغط زر **Upload** في الأعلى
4. ارفع ملف `forums.zip`
5. بعد اكتمال الرفع، اضغط كليك يمين على `forums.zip`
6. اختر **Extract** → تأكد أن مسار الاستخراج هو `public_html/`
7. بعد الاستخراج ستجد: `public_html/forums/` بداخله كل الملفات
8. احذف ملف `forums.zip` لتوفير المساحة

### الطريقة ب: عبر FTP (للملفات الكبيرة)

1. استخدم برنامج FileZilla
2. بيانات الـ FTP موجودة في: **Files** → **FTP Accounts**
3. اتصل بالسيرفر
4. ارفع مجلد `forums` إلى داخل `public_html/`

---

## الخطوة 5: إعداد ملف البيئة (.env) 📝

1. من **File Manager**، ادخل مجلد `public_html/forums/`
2. ابحث عن ملف `.env.example`
3. اضغط كليك يمين → **Rename** → سمّه `.env`
4. اضغط كليك يمين على `.env` → **Edit**
5. عدّل المحتوى كالتالي:

```env
APP_NAME="اسم منتداك هنا"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com/forums

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=اسم_قاعدة_البيانات_الخاصة_بـ_vBulletin
DB_USERNAME=يوزر_قاعدة_البيانات
DB_PASSWORD=باسوورد_قاعدة_البيانات

CACHE_STORE=file
SESSION_DRIVER=file
```

> **⚠️ مهم:** بيانات قاعدة البيانات تجدها في:
> - **Databases** → **MySQL Databases** في لوحة Hostinger
> - أو في ملف `includes/config.php` الخاص بـ vBulletin القديم

6. اضغط **Save** بعد التعديل

---

## الخطوة 6: تثبيت الحزم عبر Composer 📦

### 6.1 — فتح Terminal

1. من لوحة التحكم اذهب إلى: **Advanced** → **SSH Access**
2. إذا لم يكن SSH مفعّل، اضغط **Enable**
3. انسخ أمر الاتصال وافتح Terminal على جهازك (PowerShell أو CMD)
4. الصق أمر SSH واضغط Enter
5. أدخل كلمة المرور

> **بديل:** يمكنك استخدام **Advanced** → **Terminal** مباشرة من المتصفح (إذا متوفر في خطتك)

### 6.2 — الانتقال لمجلد المشروع

```bash
cd public_html/forums
```

### 6.3 — تثبيت Composer (إذا غير موجود)

```bash
# تحقق أولاً
composer --version

# إذا غير موجود:
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# الآن استخدم php composer.phar بدلاً من composer
```

### 6.4 — تثبيت الحزم

```bash
# إذا composer موجود عالسيرفر:
composer install --optimize-autoloader --no-dev

# إذا استخدمت composer.phar:
php composer.phar install --optimize-autoloader --no-dev
```

> **⏳ انتظر** — هذا الأمر قد يأخذ 2-5 دقائق. لا تغلق Terminal.

> **⚠️ إذا ظهر خطأ في الذاكرة:**
> ```bash
> php -d memory_limit=512M composer.phar install --optimize-autoloader --no-dev
> ```

---

## الخطوة 7: إنشاء مفتاح التطبيق 🔑

```bash
php artisan key:generate
```

ستظهر رسالة: `Application key set successfully.`

---

## الخطوة 8: إعداد مجلدات التخزين 📁

```bash
# إنشاء المجلدات المطلوبة
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# إعطاء صلاحيات الكتابة
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# ربط مجلد التخزين
php artisan storage:link
```

---

## الخطوة 9: تشغيل Migration (لجدول API فقط) 🗄️

```bash
php artisan migrate
```

> **ملاحظة مهمة:** هذا الأمر سينشئ فقط جدول `personal_access_tokens` الخاص بـ Sanctum API.
> **لن يمس أي جدول من جداول vBulletin.**

إذا طلب تأكيد، اكتب `yes`.

---

## الخطوة 10: تحسين الأداء للإنتاج ⚡

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## الخطوة 11: ضبط التوجيه (مهم جداً!) 🔀

بما أن المشروع في `public_html/forums/`، المتصفح يجب يوصل لمجلد `public/` داخل المشروع.

### الحل: تعديل ملف `.htaccess` في مجلد `forums/`

1. من **File Manager**، افتح `public_html/forums/`
2. افتح ملف `.htaccess` (ملف الجذر، ليس الذي داخل public/)
3. تأكد أن محتواه:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect everything to public folder
    RewriteCond %{REQUEST_URI} !^/forums/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

> هذا يجعل كل زيارة لـ `yourdomain.com/forums/...` تذهب تلقائياً لمجلد `public/`.

---

## الخطوة 12: تعديل robots.txt 🤖

1. من **File Manager**، افتح `public_html/forums/public/robots.txt`
2. غيّر السطر الأخير:

```
Sitemap: https://yourdomain.com/forums/sitemap.xml
```

---

## الخطوة 13: اختبار التثبيت ✅

### 13.1 — اختبار الصفحة الرئيسية
افتح في المتصفح:
```
https://yourdomain.com/forums/
```
يجب أن تظهر الصفحة الرئيسية بالتصميم الداكن.

### 13.2 — اختبار لوحة التحكم
```
https://yourdomain.com/forums/admin
```
سجّل الدخول بحساب vBulletin الذي مجموعته (usergroupid) = 5 أو 6 أو 7.

### 13.3 — اختبار Sitemap
```
https://yourdomain.com/forums/sitemap.xml
```

### 13.4 — اختبار API
```
https://yourdomain.com/forums/api/threads
```
(يجب أن يطلب توكن — هذا طبيعي)

---

## حل المشاكل الشائعة 🔧

### مشكلة: صفحة بيضاء (500 Error)

```bash
cd public_html/forums

# شغّل وضع التطوير مؤقتاً لرؤية الخطأ:
# عدّل .env واجعل APP_DEBUG=true ثم:
php artisan config:clear
php artisan cache:clear

# بعد حل المشكلة أرجعها:
# APP_DEBUG=false
php artisan config:cache
```

### مشكلة: 404 Not Found على كل الصفحات
- تأكد أن `mod_rewrite` مفعّل
- تأكد من ملف `.htaccess` في `forums/` و `forums/public/`

### مشكلة: خطأ في الاتصال بقاعدة البيانات
1. تحقق من بيانات `.env`
2. تأكد أن `DB_HOST=localhost`
3. تأكد أن اليوزر لديه صلاحيات على قاعدة البيانات

### مشكلة: خطأ Permission denied
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### مشكلة: Composer out of memory
```bash
php -d memory_limit=-1 composer.phar install --optimize-autoloader --no-dev
```

### مشكلة: Class not found
```bash
composer dump-autoload --optimize
```

---

## تحديث المنتدى مستقبلاً 🔄

إذا عدّلت الكود وأردت رفعه مرة ثانية:

```bash
cd public_html/forums

# مسح الكاش القديم
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# إعادة بناء الكاش
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## API — مرجع سريع 📡

| Method | URL | الوصف | Auth |
|--------|-----|-------|------|
| `POST` | `/forums/api/login` | تسجيل الدخول | ❌ |
| `POST` | `/forums/api/logout` | تسجيل الخروج | ✅ Bearer |
| `GET` | `/forums/api/threads` | قائمة المواضيع | ✅ Bearer |
| `GET` | `/forums/api/threads/{id}` | تفاصيل موضوع | ✅ Bearer |
| `GET` | `/forums/api/posts/{threadId}` | ردود موضوع | ✅ Bearer |

### مثال تسجيل الدخول:
```bash
curl -X POST https://yourdomain.com/forums/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"123456"}'

# الرد:
# {"token":"1|abc123...","user":{...}}
```

### مثال جلب المواضيع:
```bash
curl https://yourdomain.com/forums/api/threads \
  -H "Authorization: Bearer 1|abc123..."
```
