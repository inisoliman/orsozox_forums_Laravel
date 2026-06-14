<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-8892BF?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Bootstrap-5.3_RTL-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap">
  <img src="https://img.shields.io/badge/Filament-3.2-FBBF24?style=for-the-badge" alt="Filament">
  <img src="https://img.shields.io/badge/License-MIT-22C55E?style=for-the-badge" alt="License">
</p>

<h1 align="center">🏛️ Orsozox Orthodox Forum</h1>

<p align="center">
  <strong>منتدى أرثوذكسي عربي حديث مبني بكل ❤️ على Laravel 11</strong><br>
  يعمل فوق قاعدة بيانات vBulletin 3.8 الأصلية — بدون هجرة بيانات
</p>

<p align="center">
  <a href="https://orsozox.com/forums">🌐 الموقع المباشر</a> •
  <a href="#-الميزات">✨ الميزات</a> •
  <a href="#-التثبيت">🚀 التثبيت</a> •
  <a href="#-التوثيق">📘 التوثيق</a>
</p>

---

## 📋 نظرة عامة

هذا المشروع هو **واجهة أمامية حديثة بالكامل** لمنتدى [Orsozox](https://orsozox.com/forums) الأرثوذكسي القائم منذ **2005**. يتصل مباشرةً بقاعدة بيانات **vBulletin 3.8** الأصلية (**70,000+ موضوع**) بدون تعديل هيكل الجداول.

يعمل على **استضافة مشتركة (Shared Hosting)** مع أداء عالٍ وSEO متقدم.

---

## ✨ الميزات

### 🎨 الواجهة والتصميم
- واجهة حديثة بـ **Bootstrap 5.3 RTL** مع Dark/Light Mode
- خطوط عربية محسّنة (**Cairo + Amiri**) مع preloading
- صفحات أخطاء مخصصة (**404, 403, 419, 500, 503**) بتصميم Byzantine Elegance
- **Font Awesome 6.4** للأيقونات

### 🔍 SEO احترافي
- **Schema.org** ديناميكي (DiscussionForumPosting, FAQPage, BreadcrumbList, Organization, Person)
- **Open Graph + Twitter Cards** لكل صفحة
- **Sitemap Index** مُقسَّم (1000 موضوع/صفحة)
- **hreflang** (ar, ar-EG, x-default)
- **301 Redirects** شامل لكل روابط vBulletin/vBSEO القديمة
- كشف تلقائي للأسئلة وتوليد **FAQPage Schema**
- **robots.txt** محسَّن

### 🔐 الأمان والمصادقة
- تسجيل دخول متوافق مع **MD5+Salt** الخاص بـ vBulletin
- نظام صلاحيات مبني على جدول `forumpermission` + Bitfield
- Security Headers (HSTS, X-Frame-Options, CSP)
- حماية CSRF + XSS في BBCode

### 🚀 الأداء
- **HTML Minification** تلقائي (Middleware)
- **Gzip Compression** مزدوج (Apache + PHP)
- تحويل **WebP** ديناميكي مع تخزين مؤقت
- **Lazy Loading** للصور
- Cache معزول بـ `usergroupid` لمنع تسريب المحتوى

### 🤖 الذكاء المحلي (Local AI)
- **SpamShield** — نظام تقييم نقاط لكشف السبام
- **Anti-Duplicate** — كشف تكرار المواضيع بخوارزمية `similar_text()`
- **KeywordExtractor** — استخراج كلمات مفتاحية مع فلترة Stop Words عربية
- **ContentCleaner** — تنظيف وتطبيع النص العربي

### 🎬 YouTube Lite Embed
- تحويل تلقائي لروابط YouTube إلى **بطاقات معاينة خفيفة**
- لا iframe حتى ينقر المستخدم → **أداء أفضل بكثير**
- يدعم: `watch`, `youtu.be`, `shorts`, BBCode `[ame]`

### 🖼️ LIIMS — إدارة الصور القديمة
- **Image Proxy** ذكي للصور الخارجية المكسورة
- فحص HEAD مع حماية **SSRF**
- Placeholder جميل بطابع مسيحي للصور غير المتوفرة
- لوحة إدارة **Filament** بإحصائيات وأزرار تحكم
- **Queue-based** لفحص مجموعات كبيرة من الصور

### 🔄 البحث المتقدم
- فهارس **MySQL FULLTEXT** مع `MATCH() AGAINST()`
- بحث فوري **AJAX** مع اقتراحات
- ترجيح العنوان × 3 مقارنة بالمحتوى
- **Rate Limiting** (30/دقيقة AJAX, 10/دقيقة بحث كامل)

### 🛡️ لوحة تحكم Filament 3.2
- إدارة الأقسام والمواضيع والمشاركات والأعضاء
- شريط أخبار متحرك (CRUD كامل)
- لوحة إدارة الصور (LIIMS)
- الوصول مقيّد بـ `usergroupid ∈ {5,6,7}`

---

## 🛠️ المتطلبات

| المتطلب | الإصدار |
|---------|---------|
| PHP | 8.2+ |
| Laravel | 11.x |
| MySQL | 5.6+ (FULLTEXT on InnoDB) |
| Composer | 2.x |
| Apache | mod_rewrite مُفعَّل |

---

## 🚀 التثبيت

### 1. استنساخ المشروع

```bash
git clone https://github.com/YOUR_USERNAME/forums.git
cd forums
composer install
```

### 2. إعداد البيئة

```bash
cp .env.example .env
php artisan key:generate
```

```env
APP_NAME="منتدى أرثوذكس"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://orsozox.com/forums

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_vbulletin_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### 3. إنشاء الجداول الإضافية

```bash
# شغّل من phpMyAdmin أو SSH:
mysql -u user -p database < database/create_site_settings.sql
mysql -u user -p database < database/create_image_cache.sql
mysql -u user -p database < database/add_fulltext_indexes.sql
```

### 4. بناء الكاش

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> 📖 للتفاصيل الكاملة انظر [`INSTALL.md`](INSTALL.md)

---

## 🗂️ هيكل المشروع

```
forums/
├── app/
│   ├── Auth/
│   │   └── VBulletinUserProvider.php       ← مزوّد مصادقة vBulletin
│   ├── Console/Commands/
│   │   └── ScanImagesCommand.php           ← أمر فحص الصور
│   ├── Filament/
│   │   ├── Resources/                      ← 5 موارد إدارية
│   │   └── Pages/ManageImages.php          ← لوحة إدارة الصور
│   ├── Helpers/
│   │   ├── BBCodeParser.php                ← محلل BBCode → HTML
│   │   ├── SeoHelper.php                   ← Schema.org + OG + Meta
│   │   ├── WebpHelper.php                  ← تحويل WebP ديناميكي
│   │   └── SearchHighlightHelper.php       ← تمييز كلمات البحث
│   ├── Http/
│   │   ├── Controllers/                    ← 14+ Controller
│   │   │   ├── HomeController.php
│   │   │   ├── ForumController.php
│   │   │   ├── ThreadController.php
│   │   │   ├── SearchController.php
│   │   │   ├── ImageProxyController.php    ← بروكسي الصور
│   │   │   ├── RedirectController.php      ← 301 vBulletin + Archive + Tags
│   │   │   └── Api/                        ← 5 API controllers
│   │   └── Middleware/                     ← HtmlMinify, Gzip, Security, Session
│   ├── Jobs/
│   │   └── ScanImagesJob.php               ← فحص صور في الخلفية
│   ├── Models/                             ← 8+ نماذج بيانات
│   │   ├── Forum.php, Thread.php, Post.php
│   │   ├── User.php, ForumPermission.php
│   │   ├── Attachment.php, ImageCache.php
│   │   └── Session.php, NewsTicker.php
│   └── Services/                           ← طبقة الخدمات
│       ├── ThreadSeoService.php
│       ├── SearchService.php
│       ├── YouTubeLiteEmbedService.php     ← YouTube Lite
│       ├── ImageProxyService.php           ← بروكسي الصور
│       ├── ImageValidationService.php      ← فحص الصور
│       ├── SettingsService.php             ← إعدادات الموقع
│       └── LocalAI/                        ← ذكاء محلي
│           ├── ContentCleanerService.php
│           ├── KeywordExtractorService.php
│           └── SpamShieldService.php
├── resources/views/
│   ├── layouts/app.blade.php               ← القالب الأساسي
│   ├── errors/                             ← 404, 403, 419, 500, 503
│   └── ...                                 ← 16+ ملف عرض
├── public/
│   ├── css/                                ← app.css, yt-lite.css, error-pages.css
│   ├── js/                                 ← yt-lite.js, error-pages.js
│   └── images/image-unavailable.png        ← Placeholder
├── routes/web.php                          ← 40+ مسار
├── STRUCTURE.md                            ← هيكل المشروع التفصيلي
├── AI_CONTEXT.md                           ← سياق AI للتطوير المستقبلي
├── DOCUMENTATION_AR.md                     ← التوثيق العربي الشامل
├── DOCUMENTATION_EN.md                     ← التوثيق الإنجليزي الشامل
└── INSTALL.md                              ← دليل التثبيت والنشر
```

---

## 🌐 المسارات الرئيسية

| المسار | الوصف |
|--------|-------|
| `GET /` | الصفحة الرئيسية |
| `GET /forum/{id}/{slug?}` | صفحة القسم |
| `GET /thread/{id}/{slug?}` | صفحة الموضوع |
| `GET /user/{id}` | ملف العضو |
| `GET /search` | البحث المتقدم |
| `GET /search/suggest` | بحث فوري AJAX |
| `GET /posts/{id}` | رابط مباشر للمشاركة → 301 |
| `GET /image-proxy/{hash}` | بروكسي الصور الخارجية |
| `GET /sitemap.xml` | Sitemap Index |
| `GET /about` | من نحن (E-E-A-T) |
| `GET /contact` | اتصل بنا |
| `GET /admin` | لوحة تحكم Filament |

### تحويلات الروابط القديمة (301)

| الرابط القديم | الرابط الجديد |
|---------------|---------------|
| `showthread.php?t=123` | `/thread/123/slug` |
| `forumdisplay.php?f=5` | `/forum/5/slug` |
| `member.php?u=99` | `/user/99` |
| `f5/t123/` | `/thread/123/slug` |
| `orsozox-t123.html` | `/thread/123/slug` |
| `archive/index.php/t-123.html` | `/thread/123/slug` |
| `archive/index.php/f-5.html` | `/forum/5/slug` |
| `tags.php?tag=keyword` | `/search?q=keyword` |

---

## 🧹 أوامر صيانة الروابط داخل المقالات

هذه الأوامر مخصصة لفحص وتنظيف روابط المشاركات القديمة في جدول `post` بدون تغيير هيكل قاعدة بيانات vBulletin. كل أمر يعمل افتراضيًا في وضع معاينة آمن ولا يكتب أي تعديل إلا عند إضافة `--commit`.

### تنظيف روابط `redirector.php`

يفحص كل المقالات التي تحتوي على روابط تحويل قديمة مثل:

```text
https://www.orsozox.com/forums/redirector.php?url=http%3A%2F%2Fexample.com%2Ffile
http://www.elm7ba.com/vb4/redirector.php?url=http://example.com/file
```

ويحولها إلى الرابط الأصلي النظيف. كما يصلح الروابط التي انكسرت سابقًا بسبب التصاق رابطين معًا، مثل:

```text
http://www.elm7ba.com/vb4http://www.zahbyalfam.com/pub_newtest/43_john.html
```

لتصبح:

```text
http://www.zahbyalfam.com/pub_newtest/43_john.html
```

تشغيل المعاينة بدون تعديل قاعدة البيانات:

```bash
php artisan posts:clean-redirector-links
```

تنفيذ التعديل الفعلي بعد مراجعة التقرير:

```bash
php artisan posts:clean-redirector-links --commit
```

تقرير المواضيع المتأثرة يحفظ افتراضيًا في:

```text
storage/app/redirector-link-cleaner-report.csv
```

### تحويل الروابط النصية إلى روابط قابلة للنقر

يفحص كل المقالات التي تحتوي على روابط نصية عادية مثل:

```text
https://t.me/orsozox_file/480
www.example.com/file
```

ويحولها إلى روابط قابلة للنقر. يتجاهل الروابط الموجودة بالفعل داخل `[url]...[/url]` أو `<a href="...">`، ويتجاهل روابط الصور داخل `[img]...[/img]`.

تشغيل المعاينة بدون تعديل قاعدة البيانات:

```bash
php artisan posts:linkify-plain-urls
```

تنفيذ التعديل الفعلي بعد مراجعة التقرير:

```bash
php artisan posts:linkify-plain-urls --commit
```

تقرير المواضيع المتأثرة يحفظ افتراضيًا في:

```text
storage/app/plain-url-linkifier-report.csv
```

### خيارات مفيدة للأمرين

يمكن استخدام هذه الخيارات مع الأمرين السابقين:

```bash
--limit=100
--chunk=200
--from-id=1000
--to-id=5000
--sample=20
--report=storage/app/custom-report.csv
```

أمثلة:

```bash
php artisan posts:clean-redirector-links --limit=100
php artisan posts:linkify-plain-urls --from-id=1000 --to-id=5000
php artisan posts:clean-redirector-links --commit --report=storage/app/redirector-final.csv
```

### التأكد من اكتمال التنظيف

بعد تنفيذ أي أمر، شغله مرة أخرى بدون `--commit`. إذا ظهرت القيم التالية بصفر فهذا يعني أنه لا توجد تعديلات متبقية لهذا النوع:

```text
Posts changed: 0
Redirector/malformed links cleaned: 0
Topics affected: 0
```

أو:

```text
Posts changed: 0
Plain URLs linkified: 0
Topics affected: 0
```

ويمكن التأكد من عدم وجود روابط `redirector.php` قديمة عبر SQL:

```sql
SELECT COUNT(*) AS remaining_redirector_links
FROM post
WHERE pagetext LIKE '%redirector.php%';
```

---

## 📘 التوثيق

| الملف | المحتوى |
|-------|---------|
| [`STRUCTURE.md`](STRUCTURE.md) | هيكل المشروع التفصيلي مع كل المكونات |
| [`AI_CONTEXT.md`](AI_CONTEXT.md) | سياق مُحسَّن لمحادثات AI المستقبلية |
| [`DOCUMENTATION_AR.md`](DOCUMENTATION_AR.md) | توثيق عربي شامل (14 فصل) |
| [`DOCUMENTATION_EN.md`](DOCUMENTATION_EN.md) | توثيق إنجليزي شامل (14 chapter) |
| [`INSTALL.md`](INSTALL.md) | دليل التثبيت والنشر على Shared Hosting |

---

## 🗃️ قاعدة البيانات

يعمل المشروع على جداول **vBulletin 3.8** الأصلية بدون تعديل:

```
forum          — الأقسام
thread         — المواضيع (70,000+)
post           — المشاركات
user           — الأعضاء
forumpermission — الصلاحيات (Bitfield)
attachment     — المرفقات
session        — الجلسات (من متصل)
```

### جداول Laravel الجديدة (لا تعدل vBulletin):
```
site_settings  — إعدادات الموقع (key/value)
image_cache    — سجلات فحص الصور (pending/valid/broken)
```

---

## ⚡ الأداء

| الصفحة | وقت الاستجابة (مع Cache) |
|--------|--------------------------|
| الصفحة الرئيسية | < 500ms |
| صفحة القسم | < 300ms |
| صفحة الموضوع | < 400ms |
| البحث (FULLTEXT) | < 150ms |

---

## 📄 الرخصة

MIT License — انظر ملف [LICENSE](LICENSE)

---

<p align="center">

**صُنع بكل ❤️ وإيمان بواسطة المهندس إبراهيم نصحي (INI)**

📧 [orsozox@gmail.com](mailto:orsozox@gmail.com)

*لمجد الله وخدمة الكنيسة الأرثوذكسية* ☦️

</p>
