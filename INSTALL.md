# 🚀 دليل إصلاح وتنظيف المنتدى (تحديث: 18 فبراير)

لقد قمنا بتحديث الكود لإصلاح المشاكل التي ظهرت على الموقع الحي (مسار `/forums/` لا يعمل، وظهور أكواد HTML في العناوين).

---

## الخطوات المطلوب تنفيذها الآن على السيرفر 👇

### الخطوة 1: تحديث الملفات 📤
يجب رفع الملفات التالية واستبدال القديمة:
1. `app/Models/Forum.php` (لإصلاح العناوين في الأقسام)
2. `app/Models/Thread.php` (لإصلاح العناوين في المواضيع)
3. `app/Providers/AppServiceProvider.php` (لإزالة `/public` من الروابط)
4. `.htaccess` (موجود في مجلد `public_html/forums/`) (لإصلاح صفحة 404)

### الخطوة 2: تعديل ملف .htaccess ⚙️

افتح الملف `public_html/forums/.htaccess` وتأكد أن محتواه كالتالي بالضبط:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Handle the root request to go to public/
    RewriteRule ^$ public/ [L]
    
    # Handle all other non-public requests to go to public/
    RewriteRule ^((?!public/).*)$ public/$1 [L,NC]
</IfModule>
```

### الخطوة 3: تحديث ملف .env 📝

تأكد أن الرابط في ملف `.env` هو (بدون /public):

```env
APP_URL=https://orsozox.com/forums
```

### الخطوة 4: مسح الكاش (ضروري جداً) 🧹

شغّل هذه الأوامر عبر SSH (Terminal) من داخل مجلد `forums`:

```bash
cd public_html/forums

# مسح شامل
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# إعادة بناء
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> **ملاحظة:** إذا واجهت مشكلة في التيرمنال، يمكنك مسح الكاش بمسح محتويات مجلد `storage/framework/cache` و `views` يدوياً من File Manager.

---

## التحقق من الإصلاحات ✅

1. **الرابط الرئيسي:** ادخل `https://orsozox.com/forums/` ← يجب أن يعمل الآن بدون 404.
2. **الروابط:** اضغط على أي قسم أو موضوع ← يجب أن يكون الرابط `orsozox.com/forums/thread/...` (بدون كلمة `public`).
3. **العناوين:** لاحظ العناوين التي كانت تحتوي على `<font>` أو `<b>` ← يجب أن تظهر الآن نصوص نظيفة فقط.

### 6. إعدادات السيرفر (Shared Hosting) - هام جداً 🚨

لحل مشكلة **404 Not Found**، يجب تحديث ملفين `.htaccess`:

#### الملف الأول: في المجلد الرئيسي للمنتدى (`forums/.htaccess`)
يجب أن يحتوي على الكود التالي ليعيد التوجيه إلى مجلد `public` بشكل صحيح:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /forums/

    # Redirect /forums/public/foo to /forums/foo
    RewriteCond %{THE_REQUEST} /forums/public/([^\s?]*) [NC]
    RewriteRule ^ %1 [L,NE,R=301]

    # Handle the root request to go to public/
    RewriteRule ^$ public/ [L]

    # Handle all other non-public requests to go to public/
    RewriteRule ^((?!public/).*)$ public/$1 [L,NC]
</IfModule>
```

#### الملف الثاني: داخل مجلد `public` (`forums/public/.htaccess`)
هذا الملف موجود بالفعل، ولكن يفضل تحديثه بالمحتوى التالي لضمان حذف `index.php` من الرابط:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On
    RewriteBase /forums/public/

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Remove index.php from URL
    RewriteCond %{THE_REQUEST} /index\.php [NC]
    RewriteRule ^(.*?)index\.php$ /$1 [L,R=301,NC,NE]

    # Handle Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

> **ملاحظة:** عدم إضافة `RewriteBase /forums/public/` في الملف الثاني هو السبب الرئيسي لخطأ 404.

### 7. الخاتمة

---

## 🆕 تحديثات فبراير 2026 — ميزات جديدة

### 8. نشر YouTube Lite Embed ▶️

**ارفع الملفات التالية:**
```
app/Services/YouTubeLiteEmbedService.php
public/css/yt-lite.css
public/js/yt-lite.js
resources/views/thread/show.blade.php     (تأكد أن yt-lite.js خارج @auth)
app/Models/Post.php                        (يحتوي على Content Pipeline)
```

**امسح الكاش:**
```bash
php artisan config:clear && php artisan view:clear
```

---

### 9. نشر LIIMS — إدارة الصور القديمة 🖼️

#### الخطوة 1: إنشاء الجداول الجديدة
شغّل هذين الأمرين في **phpMyAdmin**:

```sql
-- جدول الإعدادات
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) UNIQUE NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- إعدادات افتراضية
INSERT IGNORE INTO site_settings (`key`, value) VALUES ('image_proxy_enabled', '0');
INSERT IGNORE INTO site_settings (`key`, value) VALUES ('image_auto_cleanup', '0');

-- جدول كاش الصور
CREATE TABLE IF NOT EXISTS image_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url_hash VARCHAR(64) UNIQUE NOT NULL,
    original_url TEXT NOT NULL,
    status ENUM('pending','valid','broken') DEFAULT 'pending',
    response_code INT NULL,
    content_type VARCHAR(100) NULL,
    content_length INT NULL,
    last_checked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_last_checked (last_checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### الخطوة 2: رفع الملفات
```
app/Models/ImageCache.php
app/Services/ImageProxyService.php
app/Services/ImageValidationService.php
app/Services/SettingsService.php
app/Http/Controllers/ImageProxyController.php
app/Jobs/ScanImagesJob.php
app/Console/Commands/ScanImagesCommand.php
app/Filament/Pages/ManageImages.php
resources/views/filament/pages/manage-images.blade.php
public/css/image-proxy.css
public/images/image-unavailable.png
routes/web.php                              (يحتوي على route جديد)
resources/views/thread/show.blade.php       (يحتوي على CSS link)
```

#### الخطوة 3: تفعيل النظام
1. امسح الكاش: `https://orsozox.com/forums/clear-cache.php`
2. ادخل لوحة التحكم: `/admin/manage-images`
3. فعّل Image Proxy

#### الخطوة 4: فحص الصور (اختياري)
```bash
cd public_html/forums
php artisan images:scan --limit=500 --queue
php artisan queue:work --stop-when-empty
```

---

### 10. نشر صفحات الأخطاء المخصصة 🚨

**ارفع الملفات التالية:**
```
resources/views/errors/404.blade.php
resources/views/errors/403.blade.php
resources/views/errors/419.blade.php
resources/views/errors/500.blade.php
resources/views/errors/503.blade.php
public/css/error-pages.css
public/js/error-pages.js
```

**للتجربة:** ادخل أي رابط غير موجود:
```
https://orsozox.com/forums/this-page-does-not-exist
```

**تعمل تلقائياً** — لا تحتاج إعدادات إضافية.

---

> **تم تحديث الدليل — فبراير 2026**
