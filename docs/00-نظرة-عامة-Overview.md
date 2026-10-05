# 📚 التوثيق الشامل لمنتدى أرثوذكس (Laravel + vBulletin 3.8)

> هذا المجلد `docs/` هو **المرجع الكامل** للمشروع. عند الرجوع إليه ستفهم:
> التخطيط، المعمارية، الترابط بين كل جزء، المشاكل، والحلول.

---

## 🎯 ما هو هذا المشروع؟

منتدى حديث مبني على **Laravel 11 + Filament 3**، لكنه **لا يملك قاعدة بيانات خاصة به**.
بدلاً من ذلك، هو "واجهة حديثة" تقرأ وتكتب مباشرة في **قاعدة بيانات vBulletin 3.8 القديمة** كما هي،
**بدون أي تعديل على بنية الجداول الأصلية** (`user`, `forum`, `thread`, `post`, `session` ...).

الفكرة باختصار:
```
vBulletin 3.8 (قاعدة بيانات قديمة) ──يقرأها──> Laravel 11 (واجهة + لوحة تحكم حديثة)
```

- الزوّار يرون منتدى حديث سريع (Laravel + Blade + Bootstrap RTL).
- المشرفون يديرونه من لوحة **Filament**.
- جداول جديدة قليلة فقط أُضيفت لميزات حديثة (نشرة بريدية، إعدادات، أخبار، كاش صور، jobs).

---

## 🧱 المكوّنات التقنية (Stack)

| المكوّن | الإصدار | الدور |
|---------|---------|-------|
| PHP | ^8.2 | لغة التشغيل |
| Laravel | ^11.0 | الإطار الأساسي |
| Filament | ^3.0 | لوحة تحكم الإدارة (Admin Panel) |
| Sanctum | ^4.0 | حماية الـ API |
| Intervention Image | ^3.11 | معالجة الصور / WebP / علامة مائية |
| Guzzle | ^7.8 | طلبات HTTP خارجية (بروكسي الصور) |
| spatie/laravel-sitemap | ^7.2 | مُثبّت لكن **غير مستخدم فعلياً** (الخرائط مكتوبة يدوياً) |

**الاستضافة:** Hostinger — **استضافة مشتركة (Shared Hosting)** على LiteSpeed.
هذه نقطة بالغة الأهمية لأنها تفرض قيوداً (لا يوجد queue worker دائم، حدود إرسال إيميلات، إلخ).

**التطبيق منشور داخل مجلد فرعي:** `https://orsozox.com/forums/`
> `APP_URL=https://orsozox.com/forums`  ← لاحظ وجود `/forums` في النهاية. هذا أصل عدة مشاكل (انظر مستند المشاكل).

---

## 🗂️ خريطة المجلدات الأساسية

```
forums live/
├── app/
│   ├── Auth/                  # موفّر مصادقة vBulletin المخصص (verify password بنظام vB)
│   ├── Console/Commands/      # أوامر artisan مخصصة (cron / صيانة)
│   ├── Filament/              # لوحة التحكم: Resources + Pages + Widgets
│   ├── Helpers/               # BBCode, HtmlSanitizer, SEO, WebP, SearchHighlight
│   ├── Http/
│   │   ├── Controllers/       # كل صفحات الموقع الأمامية + API
│   │   └── Middleware/        # Firewall للبوتات + جلسة vB + أمان + ضغط
│   ├── Jobs/                  # مهام الطابور (إيميلات، فحص صور) ← مرتبطة بمشكلة الإيميل
│   ├── Models/                # نماذج مرتبطة بجداول vBulletin + جداول حديثة
│   ├── Policies/              # صلاحيات (ThreadPolicy, PostPolicy)
│   ├── Providers/             # تسجيل موفّر مصادقة vBulletin + خدمات
│   └── Services/              # منطق الأعمال (Online users, Search, Email validation...)
├── config/                    # ⚠️ ناقص: mail.php, queue.php, services.php غير منشورة
├── database/migrations/       # الجداول الحديثة فقط (ليست جداول vBulletin)
├── resources/views/           # قوالب Blade (الواجهة الأمامية)
├── routes/web.php             # كل مسارات الموقع
└── public/                    # نقطة الدخول + ملفات تشخيص (cc.php, clear-cache.php ...)
```

---

## 📄 فهرس ملفات التوثيق

| الملف | المحتوى |
|-------|---------|
| `00-نظرة-عامة-Overview.md` | (هذا الملف) ملخص عام + Stack + خريطة المجلدات |
| `01-المعمارية-Architecture.md` | تفاصيل المعمارية: المصادقة، النماذج، الراوتس، الـ Middleware + **بنية جدول thread الفعلية (§10)** |
| `02-المشاكل-والحلول-Issues.md` | المشاكل الست: السبب الجذري + الحل خطوة بخطوة |
| `03-قائمة-الفحص-Checklist.md` | خطوات التطبيق على الاستضافة + اختبار النتائج |
| `04-الكاش-والكرون-Cache-Cron.md` | إعدادات الكاش والكرون |
| `05-أوامر-جاهزة-Ready-Commands.md` | أوامر phpMyAdmin لاستكشاف بنية القاعدة + أوامر الكاش والرفع |
| `06-بنية-قاعدة-البيانات-Database-Schema.md` | **بنية القاعدة الكاملة**: 188 جدولاً مفهرسة حسب الفئة + بنية الجداول الأساسية تفصيلياً |

---

## ⚡ ملخص سريع للمشاكل الست (التفاصيل في مستند المشاكل)

| # | المشكلة | السبب الجذري باختصار |
|---|---------|----------------------|
| 1 | لا تصل أي إيميلات | `QUEUE_CONNECTION=database` بدون queue worker على استضافة مشتركة → المهام عالقة في جدول `jobs` ولا تُنفّذ. + `config/mail.php` غير منشور. + احتمال حجب Gmail SMTP. |
| 2 | جوجل لا يقرأ `sitemap.xml` الرئيسي (0 صفحات) لكنه يقرأ `sitemap-forums.xml` | **السبب الحقيقي (فحص حي):** الجذر `orsozox.com` WordPress، و`orsozox.com/sitemap_index.xml` خريطة Yoast فيها 153 خريطة فرعية لمقالات WP بلا أي منتدى. خريطة المنتدى `…/forums/sitemap.xml` تعمل ممتازاً (168+1000 رابطة لكل ملف، كلها 200). الحل خارج المشروع: أضف `https://orsozox.com/forums/sitemap.xml` مباشرةً في Search Console أو أدرجها داخل فهرس Yoast. |
| 3 | خطأ 500 في صفحة `/online-users` بعد الدخول | يُرجَّح: تعارض accessor الـ `lastactivity` (Carbon) + ترقيم `simplePaginate` + احتمال اختلاف أعمدة جدول `session` في vB. يحتاج تأكيد من اللوج. |
| 4 | لا تظهر بوتات محركات البحث في "المتواجدون الآن" | `UpdateLegacySession` **يتخطّى البوتات عمداً** فلا تُكتب في جدول `session` إطلاقاً، لذلك العدّاد دائماً صفر. |
| 5 | الرد السريع "خطأ في الاتصال بالسيرفر" في بعض المواضيع | مسار `/thread/{id}/posts-fragment` كان بعد المسار العمومي `/thread/{id}/{slug?}` فعاد 301 كنسي + كاش 301 قديم (30 يوماً) في المتصفح/Cloudflare. الحل: إعادة الترتيب + معامل `&_=Date.now()`. |
| 6 | إنشاء موضوع جديد يعطي 500 (الواجهة + الإدارة) | جدول `thread` المهاجَر يفتقد `lastposterid`, `deluserid`, `deldate` (كان الكود يعيّنها → Unknown column)؛ و`similar` نصي وليس عددياً. الحل: مطابقة `$attributes` في النموذج للبنية الفعلية (راجع §10 في المعمارية). ✅ **مؤكّد حله** — "كل شيء يعمل جيدا". |

> 🎯 **قاعدة ذهبية للتطوير:** قبل تعديل أي كود يقرأ/يكتب جداول vBulletin، شغّل الأمر 0.2 من
> `05-أوامر-جاهزة` (بنية كل الجداول) وقارن الأعمدة مع النموذج — لا تخمّن البنية أبداً.
> بنية الجداول الأساسية موثّقة في `06-بنية-قاعدة-البيانات-Database-Schema.md`.

---

## 🆕 ميزات المراجعة (Moderation) — أُضيفت 2026-08-19

بعد تصحيح مشكلة #2 اكتُشف أن السبب الجذري الحقيقي في جذر WordPress (خريطة Yoast بلا محتوى منتدى) — انظر `02-المشاكل-والحلول`. وأُضيفت ميزات إدارة جديدة بدون أي تعديل على بنية القاعدة:

### 1) المواضيع والردود قيد المراجعة (`visible = 0`)
- المواضيع التي أوقفها `SpamShieldService` (سكور > 80) أو عدّلها الأدمن تبقى `visible=0`.
- موارد Filament جديدة تحت مجموعة **"المراجعة"** في لوحة التحكم:
  - `PendingThreadResource` → مواضيع `visible=0` مع إجراء **"موافقة ونشر"** (ينشر أول مشاركة + `forum.threadcount++` + `user.posts++`) + موافقة جماعية + حذف.
  - `PendingPostResource` → ردود `visible=0` (باستثناء `firstpostid`) مع موافقة تحدّث `thread.replycount++` و`lastpost` و`user.posts++`.
  - `PendingVisitorMessageResource` → رسائل الزوار بحالة `state='moderation'` مع موافقة (تغيير `state` إلى `visible`).

> ⚠️ **بدون ازدواج في العدادات:** عند الإنشاء تزداد العدادات فقط للعناصر المرئية
> (`ThreadController::store` السطر 143، `ReplyController::createReply` السطر 87) — لذلك الموافقة لاحقاً تُكمّل الناقص فقط.

### 2) رسائل الزوار على الصفحة الشخصية
- جدول vBulletin `visitormessage` (بدون تعديل بنيته) + نموذج `app/Models/VisitorMessage.php`.
- صفحة العضو أُعيد تصميمها (تبويبات: المواضيع | الردود | رسائل الزوار) — `resources/views/user/show.blade.php`.
- المسار `POST /user/{id}/message` → `UserController::storeVisitorMessage` (تحقق + تنقية HTML + فحص سبام → `state` = `visible` أو `moderation`).

### 3) ترتيب "المتواجدون الآن"
- في `OnlineUsersService::getOnlineUsers()` أصبح الترتيب: **الأعضاء المسجلون أولاّ → البوتات → الزوار**، ثم حسب `lastactivity DESC`، عبر `orderByRaw(CASE ...)` (فرز في SQL حتى لا ينكسر الترقيم عبر الصفحات).

### 4) المراجعة من داخل المنتدى (بدون الدخول للوحة الإدارية) — إصلاح 2026-08-19
- `app/Services/ModerationService.php` — منطق الموافقة المركزي (thread/post/visitormessage) تستخدمه لوحة Filament **و** أزرار المنتدى الأمامية.
- `app/Http/controllers/ModerationController.php` + مسارات `POST /moderation/{thread|post|message}/{id}/{approve|reject}` (auth + صلاحية أدمن/مشرف داخل المتحكم).
- لوحات ذهبية أمام المشاهد: مواضيع معلقة في صفحة القسم، ردود معلقة داخل الموضوع، رسائل معلقة على صفحة العضو — أزرار موافقة/رفض فورية (`public/js/moderation.js`).

### 5) معاينة المحتوى كاملاّ في لوحة الإدارة
- موارد المراجعة تعرض "معاينة المحتوى" داخل نافذة منبثقة تعمل عبر `parsed_content` (HTML مُصيّر وليس كود) — `resources/views/filament/preview-content.blade.php`.

> ⚠️ **بعد رفع الملفات**: نفّذ `php artisan view:clear && php artisan config:clear && php artisan route:clear && php artisan optimize:clear`
> لقد كان سبب سابق لمشكلة "الموافقة لا تعمل" هو كاش الخادم (views/config) من رفعة سابقة ناقصة.

---

> الخطوة التالية: اقرأ `02-المشاكل-والحلول-Issues.md` لرؤية الحل التفصيلي لكل مشكلة.
