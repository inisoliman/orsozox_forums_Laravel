# 🏛️ منتدى أرثوذكسي — Laravel Modern Forum

> منتدى عربي حديث مبني على Laravel 11، يعمل على قاعدة بيانات vBulletin 3.8 الحالية بدون أي هجرة للبيانات.

[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📋 نظرة عامة

هذا المشروع هو واجهة أمامية حديثة لمنتدى **vBulletin 3.8** القائم، يتصل مباشرةً بقاعدة البيانات الأصلية دون هجرة أو تعديل في هيكل الجداول. يوفر:

- 🎨 **واجهة حديثة** — Bootstrap 5 RTL مع دعم Light/Dark Mode
- 🔍 **SEO متقدم** — Meta Tags + Schema.org + Sitemap Index + hreflang  
- 🔐 **تسجيل دخول vBulletin** — MD5+Salt Hash متوافق مع نظام الأعضاء الأصلي
- 🛡️ **نظام صلاحيات** — مبني على جدول `forumpermission` الأصلي
- ⚡ **لوحة تحكم Filament** — إدارة المواضيع والأعضاء والأقسام
- 🔗 **تحويل روابط 301** — لحماية SEO عند الانتقال من vBulletin

---

## 🛠️ المتطلبات

| المتطلب | الإصدار |
|---------|---------|
| PHP | 8.2+ |
| Laravel | 11.x |
| MySQL | 5.7+ (قاعدة بيانات vBulletin) |
| Composer | 2.x |
| Apache | mod_rewrite مُفعَّل |

---

## 📦 الحزم المستخدمة

```json
{
  "filament/filament": "^3.2",
  "laravel/framework": "^11.0",
  "laravel/sanctum": "^4.0",
  "laravel/tinker": "^2.9"
}
```

---

## 🚀 التثبيت على Hostinger Shared Hosting

### 1. رفع الملفات

```bash
# ارفع كل ملفات المشروع عدا مجلد /public إلى:
/home/username/forums/

# ارفع محتوى /public إلى:
/home/username/public_html/forums/public/
```

### 2. إعداد ملف `.env`

```env
APP_NAME="منتدى أرثوذكس"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://orsozox.com/forums

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_vbulletin_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### 3. تشغيل أوامر Artisan

```bash
php artisan key:generate
php artisan storage:link
php artisan optimize
php artisan cache:clear
```

### 4. إعداد `.htaccess`

تأكد من أن الملف الموجود في جذر المشروع يحتوي على `RewriteBase /forums/`.

---

## 🗂️ هيكل المشروع

```
forums/
├── app/
│   ├── Auth/
│   │   └── VBulletinUserProvider.php  ← تسجيل الدخول بنظام vBulletin
│   ├── Filament/Resources/            ← موارد لوحة التحكم
│   ├── Helpers/
│   │   ├── BBCodeParser.php           ← تحويل BBCode إلى HTML
│   │   └── SeoHelper.php              ← مساعد SEO
│   ├── Http/Controllers/
│   │   ├── HomeController.php
│   │   ├── ForumController.php
│   │   ├── ThreadController.php
│   │   ├── UserController.php
│   │   ├── SearchController.php
│   │   ├── AuthController.php
│   │   ├── SitemapController.php
│   │   └── RedirectController.php     ← تحويل روابط vBulletin القديمة
│   └── Models/
│       ├── Forum.php
│       ├── Thread.php
│       ├── Post.php
│       ├── User.php
│       ├── Attachment.php
│       └── ForumPermission.php
├── public/
│   ├── css/app.css                    ← CSS مع Light/Dark Mode
│   └── robots.txt
├── resources/views/
│   ├── layouts/app.blade.php          ← القالب الرئيسي
│   ├── home.blade.php
│   ├── forum/show.blade.php
│   ├── thread/show.blade.php
│   ├── user/show.blade.php
│   ├── search.blade.php
│   ├── auth/login.blade.php
│   └── errors/forbidden.blade.php    ← صفحة رفض الصلاحية
├── routes/
│   ├── web.php
│   └── api.php
├── .htaccess                          ← قواعد Rewrite + vBulletin Redirects
├── .env.example
└── INSTALL.md
```

---

## 🌐 المسارات (Routes)

| المسار | الوصف |
|--------|-------|
| `GET /` | الصفحة الرئيسية |
| `GET /forum/{id}/{slug?}` | صفحة القسم |
| `GET /thread/{id}/{slug?}` | صفحة الموضوع |
| `GET /user/{id}` | ملف العضو |
| `GET /search` | نموذج البحث |
| `GET /search/results` | نتائج البحث |
| `GET /login` | صفحة تسجيل الدخول |
| `POST /login` | تسجيل الدخول |
| `GET /sitemap.xml` | Sitemap Index |
| `GET /sitemap-forums.xml` | Sitemap الأقسام |
| `GET /sitemap-threads-{page}.xml` | Sitemap المواضيع (مُقسَّم) |
| `GET /showthread.php?t={id}` | تحويل 301 من vBulletin |
| `GET /forumdisplay.php?f={id}` | تحويل 301 من vBulletin |
| `/admin` | لوحة تحكم Filament |

---

## 🔐 نظام تسجيل الدخول

يستخدم المشروع تشفير كلمات مرور **vBulletin** الأصلي:

```php
$hash = md5(md5($password) . $user->salt);
```

> ⚠️ **تحذير:** هذا التشفير أقل أماناً من bcrypt. يُستخدم للتوافق مع قاعدة البيانات الأصلية فقط.

---

## 🛡️ نظام الصلاحيات

يعتمد على جدول `forumpermission` الأصلي:

| usergroupid | المجموعة |
|-------------|----------|
| 1 | زوار غير مسجلين |
| 2 | أعضاء مسجلون |
| 5 | مشرفون |
| 6 | مشرفون عامون |
| 7 | مدراء |

الـ Bit 1 في `forumpermissions` = صلاحية رؤية القسم (canview).  
المشرفون والمدراء (5,6,7) يرون جميع الأقسام دائماً.

---

## 📊 SEO

- ✅ Meta Title + Description + Canonical
- ✅ Open Graph (og:title, og:description, og:type, og:locale=ar_AR)
- ✅ Twitter Card
- ✅ hreflang (ar, ar-EG, x-default)
- ✅ Schema.org `DiscussionForumPosting` + `BreadcrumbList`
- ✅ Sitemap Index مُقسَّم (كل 1000 موضوع)
- ✅ robots.txt محسَّن
- ✅ 301 Redirects من روابط vBulletin القديمة

---

## 🔄 تحويلات الروابط القديمة

| رابط vBulletin القديم | الرابط الجديد |
|-----------------------|--------------|
| `showthread.php?t=123` | `/thread/123/slug` |
| `forumdisplay.php?f=5` | `/forum/5/slug` |
| `member.php?u=99` | `/user/99` |
| `f5/t123/` | `/thread/123` |
| `orsozox-t123.html` | `/thread/123` |

---

## 👨‍💻 المساهمة في التطوير

1. Fork المشروع
2. أنشئ Branch جديد: `git checkout -b feature/your-feature`
3. Commit التغييرات: `git commit -m 'Add: feature description'`
4. Push: `git push origin feature/your-feature`
5. افتح Pull Request

---

## 📄 الرخصة

MIT License — انظر ملف [LICENSE](LICENSE)
