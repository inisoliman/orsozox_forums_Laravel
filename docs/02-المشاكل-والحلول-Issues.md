# 🛠️ المشاكل والحلول — Issues & Fixes

لكل مشكلة: **العَرَض → السبب الجذري → الحل خطوة بخطوة**.

---

## ❌ المشكلة #0 — اختفاء زر «تعديل» عن صاحب الموضوع/الرد

### العَرَض
الأدمن/المشرف يستطيع تعديل مواضيع وردود **أي عضو**، لكن **صاحب المنشور نفسه** لا يظهر
له زر «تعديل» على مواضيعه/ردوده بعد نشرها (يظهر فقط على مواضيعه قيد المراجعة).

### السبب الجذري
منطق مقلوب في السياسات (`Policies`). كان في `app/Policies/PostPolicy.php`:
```php
if ($user->userid === (int) $post->userid) {
    return (int) $post->visible === 0;   // ← سماح بالتعديل فقط أثناء «قيد المراجعة»
}
return app(ModerationPermissionService::class)->can($user, 'edit_post', null, $post);
```
أي أن **المالك** يُسمح له بالتعديل فقط عندما `visible === 0` (قيد المراجعة)، فيختفي الزر
بعد الموافقة (`visible === 1`). ونفس الخطأ في `app/Policies/ThreadPolicy.php`
مع `$thread->postuserid` و `$thread->visible`.
لذلك كان المشرف (Team Work) يمر عبر فرع `ModerationPermissionService::can(...)`
فيرى الزر على منشورات الآخرين، بينما صاحب المنشور لا يراه على منشوره.

### ✅ الحل (تم)
سطر واحد في كل سياسة: المالك يستطيع التعديل دائماً ما لم يكن المنشور **محذوفاً** (`visible === 2`):
```php
// PostPolicy::update
if ($user->userid === (int) $post->userid) {
    return (int) $post->visible !== 2;
}

// ThreadPolicy::update
if ($user->userid === (int) $thread->postuserid) {
    return (int) $thread->visible !== 2;
}
```
- المالك: يُعدّل منشوره عند `visible = 0` (قيد المراجعة) أو `1` (منشور)، ولا يُعدّله عند `2` (محذوف).
- غير المالك: يمر كما هو إلى `ModerationPermissionService::can(...)` (المشرف/الأدمن فقط).

### الملفات المعدّلة
- `app/Policies/PostPolicy.php`
- `app/Policies/ThreadPolicy.php`

> مواضع الزر التي استفادت من الإصلاح تلقائياً:
> `resources/views/thread/partials/post.blade.php` (`@can('update', $post)`) و
> `resources/views/thread/show.blade.php` (`$viewer->can('update', $thread)`) وواجهات
> `Api/PostEditController` و `Api/ThreadEditController` و `Api/ThreadActionController`.

### النشر
ارفع الملفين + `php artisan view:clear` (لا يلزم مسح كاش المسارات).

---

## ❌ المشكلة #1 — لا تصل أي إيميلات إطلاقاً

### العَرَض
لا يصل أي إيميل (حملات النشرة البريدية، نموذج التواصل) رغم أن إعدادات SMTP موجودة في `.env`.

### السبب الجذري (ثلاثة أسباب مجتمعة)

**(أ) الطابور معطّل عملياً — السبب الأهم.**
في `.env` يوجد **سطرين متعارضين**:
```env
QUEUE_CONNECTION=sync
...
QUEUE_CONNECTION=database
```
في ملفات `.env` **السطر الأخير يفوز** → القيمة الفعلية = `database`.
كل الإيميلات تُرسَل عبر `SendCampaignEmailJob implements ShouldQueue`.
مع `database`، الـ Job يُحفظ في جدول `jobs` وينتظر **`php artisan queue:work`**.
على استضافة Hostinger المشتركة **لا يوجد عامل طابور (worker) يعمل باستمرار** → المهام تتراكم في جدول `jobs` ولا تُنفَّذ أبداً → **لا يُرسَل أي إيميل**.

**(ب) `config/mail.php` غير منشور.**
المجلد `config/` لا يحتوي `mail.php` ولا `queue.php`. Laravel 11 يعتمد على افتراضات داخلية، وهذا هشّ خصوصاً مع `php artisan config:cache` على الإنتاج.

**(ج) Gmail SMTP على استضافة مشتركة كثيراً ما يُحجب.**
المنفذ 587 إلى `smtp.gmail.com` قد يكون مغلقاً من Hostinger، وكلمة مرور التطبيق قد تنتهي. الأفضل استخدام SMTP الخاص بـ Hostinger (`smtp.hostinger.com`) بالبريد `admin@orsozox.com`.

### ✅ الحل

**الخطوة 1 — أصلح `QUEUE_CONNECTION` (الأهم):**
الحل الأبسط والأنسب لاستضافة مشتركة = الإرسال المباشر (متزامن):
```env
QUEUE_CONNECTION=sync
```
> احذف السطر المكرر `QUEUE_CONNECTION=database` نهائياً واترك `sync` فقط.
> مع `sync` يُرسَل الإيميل فوراً عند الطلب دون الحاجة لعامل طابور.
> (إن أردت لاحقاً إرسالاً مجدولاً للحملات الكبيرة، نستخدم cron + `queue:work --stop-when-empty` — انظر الحل البديل أدناه.)

**الخطوة 2 — انشر `config/mail.php` و `config/queue.php`** (سننشئهما) واضبط البريد على Hostinger:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=admin@orsozox.com
MAIL_PASSWORD=<كلمة مرور بريد Hostinger>
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=admin@orsozox.com
MAIL_FROM_NAME="منتديات أرثوذكس"
```
> إن أصرّينا على Gmail: `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=465`, `MAIL_ENCRYPTION=ssl`, واستخدم App Password صحيحة. لكن بريد الدومين أفضل لتفادي مجلد الـ Spam.

**الخطوة 3 — اختبر الإرسال** عبر Tinker:
```bash
php artisan tinker
>>> Mail::raw('اختبار', fn($m) => $m->to('your@email.com')->subject('Test'));
```
إن نجح هنا، فالمشكلة كانت الطابور.

**الحل البديل (للحملات الكبيرة مع cron):**
أبقِ `QUEUE_CONNECTION=database` لكن أضِف cron في Hostinger كل دقيقة:
```
* * * * * cd /home/USER/domains/orsozox.com/forums && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

---

## ❌ المشكلة #2 — جوجل لا يقرأ `sitemap.xml` الرئيسي (0 صفحات)، لكنه يقرأ `sitemap-forums.xml`

### العَرَض
في Search Console:
- `https://orsozox.com/sitemap.xml` أو `sitemap_index.xml` (على **جذر الدومين**) → **0 pages منتدى**.
- `https://orsozox.com/forums/sitemap-forums.xml` → **178 pages** ✅ (تقرأ جيداً).

### السبب الجذري الحقيقي (مكتشف بالفحص الحي 2026-08-19) — 🎯 مهم

**الموقع الجذر `orsozox.com` يديره WordPress، وليس Laravel.** لدينا **ملفا robots.txt**:

1. `https://orsozox.com/robots.txt` (WordPress — **الجذر**) يصرّح:
   ```
   Sitemap: https://orsozox.com/sitemap_index.xml        ← أول خريطة (WordPress/Yoast)
   sitemap: https://orsozox.com/forums/sitemap.xml
   sitemap: https://orsozox.com/bible/bible-sitemap.xml
   ```
2. `https://orsozox.com/forums/robots.txt` (Laravel) يصرّح:
   ```
   Sitemap: https://orsozox.com/forums/sitemap.xml
   ```

**عندما يفتح جوجل الخريطة الأولى في الجذر `orsozox.com/sitemap_index.xml`** يجد **خريطة فهرس Yoast لمدونة WordPress** بها **153 خريطة فرعية** (`post-sitemap1.xml … post-sitemap153.xml`) — **كلها مقالات WordPress، وليس فيها أي رابط منتدى إطلاقاً**. لذلك صفحة "الخريطة الرئيسية" تظهر **0 صفحات منتدى**.

في المقابل، `orsozox.com/forums/sitemap.xml` (خريطة Laravel) **تعمل بشكل ممتاز** وتُدرج كل شيء:
- `sitemap-forums.xml` (168 رابطاً) ✅
- `sitemap-threads-1.xml … N.xml` (1000 رابط لكل ملف، كلها HTTP 200 + XML سليم) ✅
- صفحات المواضيع نفسها `index, follow` (قابلة للفهرسة) ✅

**الخلاصة:** خريطة المنتدى ليست هي "الخريطة الرئيسية" التي يقرأها جوجل من الجذر. الجذر يقرأ خريطة WordPress الخالية من المنتدى.

### ✅ الحل (إجراء على مستوى WordPress — خارج مشروع Laravel)

**الخيار أ (المُوصى به — الأدق):** في Google Search Console أضف الخريطة الصحيحة:
```
https://orsozox.com/forums/sitemap.xml
```
(وليس `orsozox.com/sitemap.xml` التي هي خريطة WordPress).

**الخيار ب — أدرج خريطة المنتدى داخل فهرس WordPress:**
افتح Yoast SEO → XML Sitemaps (أو عدّل `sitemap_index.xml` يدوياً عبر WordPress/أداة) وأضف:
```xml
<sitemap>
  <loc>https://orsozox.com/forums/sitemap.xml</loc>
</sitemap>
```

**الخيار ج — أعد ترتيب `robots.txt` في الجذر** ليجعل خريطة المنتدى أول سطر:
```txt
Sitemap: https://orsozox.com/forums/sitemap.xml
Sitemap: https://orsozox.com/sitemap_index.xml
Sitemap: https://orsozox.com/bible/bible-sitemap.xml
```
> جوجل يقرأ كل أسطر `Sitemap:`، لكن الترتيب والاكتشاف أوضح عندما تكون خريطة المنتدى أولاً.

### ✅ ما تأكدنا أنه صحيح (لا يحتاج تعديلاً)
- `orsozox.com/forums/sitemap.xml` → 200 + `Content-Type: application/xml` ✅
- الخرائط الفرعية كلها 200 + XML سليم ✅ (فحص فعلي لـ threads-1 و threads-2)
- صفحات المواضيع → `index, follow` (لا يوجد noindex خاطئ) ✅
- ملف `public/robots.txt` الخاص بالمنتدى سليم ويسمح بالخرائط ✅

---

## ❌ المشكلة #3 — خطأ 500 في صفحة `/online-users` بعد تسجيل الدخول

### العَرَض
فتح `https://orsozox.com/forums/online-users` بعد الدخول كأدمن → خطأ 500.

### مسار الكود
`OnlineUsersController@index` → يتحقق من `Auth::user()->is_admin` → يستدعي `OnlineUsersService::getOnlineUsers()` → يعرض `online.index`.

### الأسباب الجذرية المحتملة (مرتّبة من الأرجح)

**(أ) تعارض في الترقيم `simplePaginate` + استدعاء `links('pagination::bootstrap-5')`.**
في الـ Service: `Session::...->simplePaginate(20)`.
في الـ View (السطر 105): `{{ $paginator->links('pagination::bootstrap-5') }}`.
- `simplePaginate` يُنتج `Paginator` (سابق/تالي فقط). تمرير اسم view ترقيم غير مُعرّف/غير منشور قد يرمي استثناء.
- مع وجود التطبيق في مجلد فرعي `/forums`، روابط الترقيم قد تتولّد بشكل غير متوقع.

**(ب) `getRawOriginal('lastactivity')` على نموذج فيه accessor يحوّل القيمة لـ Carbon.**
`Session::getLastActivityAttribute()` يحوّل `lastactivity` إلى `Carbon`. الـ Service يحاول قراءة القيمة الخام بـ `getRawOriginal('lastactivity')` وهذا صحيح، لكن إن كانت `null` في بعض الصفوف فقد يفشل `Carbon::createFromTimestamp(null)`.

**(ج) اختلاف أعمدة جدول `session` في vBulletin.**
الكود يفترض وجود أعمدة `useragent`, `host`, `location`, `loggedin`, `bypass`, `badlocation`. بعض تنصيبات vBulletin 3.8 قد تختلف، فينتج خطأ "Unknown column".

**(د) علاقة `with('user')` ترجع مستخدماً null لكن الـ View يفترض `member`.**
في الـ Service `type='member'` فقط إذا `$session->user` موجود، لكن لو حدث تعارض في المفتاح قد يصبح null.

> **لتأكيد السبب الفعلي بدقة 100%: افتح `storage/logs/laravel.log` بعد توليد الخطأ وانسخ آخر سطور الـ stack trace.**

### ✅ الحل (دفاعي شامل — يعالج كل الاحتمالات)

**الخطوة 1 — استبدل `simplePaginate` بـ `paginate` وبسّط استدعاء الترقيم في الـ View.**
- في الـ Service: استخدم `->paginate(20)` (يُنتج `LengthAwarePaginator` يدعم كل views الترقيم).
- في الـ View: استخدم `{{ $paginator->links() }}` (افتراضي) أو تأكد من نشر views الترقيم:
  ```bash
  php artisan vendor:publish --tag=laravel-pagination
  ```

**الخطوة 2 — حصّن قراءة `lastactivity` ضد null:**
```php
$lastActivityInt = (int) ($session->getRawOriginal('lastactivity') ?: time());
```
(موجود بالفعل لكن نتأكد منه ونضيف `try/catch` حول بناء الصف.)

**الخطوة 3 — لفّ منطق الصفوف بـ try/catch** حتى لا يكسر صفٌّ واحد الصفحة كلها.

**الخطوة 4 — بعد إصلاح المشكلة #4 (تسجيل البوتات)** ستظهر البوتات هنا تلقائياً.

> سنطبّق هذه التعديلات على `OnlineUsersService.php` و `online/index.blade.php`.

---

## ❌ المشكلة #4 — لا تظهر بوتات محركات البحث في "المتواجدون الآن"

### العَرَض
عدّاد "عناكب البحث" دائماً = 0، ولا يظهر أي Googlebot/Bingbot في الجدول.

### السبب الجذري (مؤكَّد 100%)
في `app/Http/Middleware/UpdateLegacySession.php` (السطور 44-48):
```php
$userAgent = $request->userAgent() ?? '';
if ($this->isBot($userAgent)) {
    return $response;     // ← البوتات تُتخطّى ولا تُكتب في جدول session أبداً
}
```
بما أن البوتات **لا تُسجَّل إطلاقاً** في جدول `session`، فإن `OnlineUsersService` لا يجد أي بوت ليعدّه. العدّاد دائماً صفر — وهذا سلوك مقصود في الكود لكنه يتعارض مع رغبتك في رؤية البوتات.

### ✅ الحل
نعدّل `UpdateLegacySession` بحيث **يسجّل البوتات أيضاً** (بـ throttle أطول لتقليل الحِمل) بدلاً من تخطّيها كلياً:

- بدل `return $response` للبوت، نسجّل جلسة خفيفة للبوت مع throttle أطول (مثلاً مرة كل 5 دقائق لكل IP+UA) لتفادي إثقال قاعدة البيانات.
- نُبقي تخطّي الملفات الثابتة كما هو.

بهذا ستظهر Googlebot/Bingbot وغيرها في صفحة "المتواجدون الآن"، ويعمل عدّاد `total_bots` في `OnlineUsersService` (الذي يكتشف البوت بـ `isBot()` من الـ `useragent` المخزّن).

> **تنبيه:** يجب الموازنة — تسجيل كل البوتات قد يضيف صفوفاً كثيرة لجدول `session`. لذلك نستخدم throttle أطول للبوتات + ننظّف الجلسات القديمة (الـ garbage collector الموجود أصلاً).

---

## ❌ المشكلة #5 — الرد السريع يفشل في بعض المواضيع: "خطأ في الاتصال بالسيرفر"

### العَرَض
زر "رد سريع" يظهر خطأ "خطأ في الاتصال بالسيرفر." في بعض المواضيع (خصوصاً متعددة الصفحات)، رغم أن الرد يُنشأ فعلاً أحياناً.

### السبب الجذري (سببان متعاقبان)

**(أ) ترتيب المسارات — السبب الأصلي.**
في `routes/web.php` كان المسار `GET /thread/{id}/posts-fragment` مسجّلاً **بعد** المسار العمومي `GET /thread/{id}/{slug?}`. لارافيل يطابق بالترتيب، فكانت كلمة `posts-fragment` تُفسَّر كـ `slug` → يُعيد 301 إلى الرابط الكنسي `/thread/{id}/{slug}`. طلب AJAX يستدعي `response.json()` على استجابة 301 → رمي استثناء → الـ catch الخارجي → "خطأ في الاتصال بالسيرفر". في المواضيع أحادية الصفحة كان الرد يتم عبر POST منفصل فنادراً ما يظهر الخطأ.

**(ب) كاش 301 قديم — السبب بعد الإصلاح.**
حتى بعد إصلاح ترتيب المسار، كان المتصفح/Cloudflare يخدم **301 مخزّناً قديماً** (مدته `max-age=2592000` = 30 يوماً) لمسار الـ fragment. الـ POST ينجح فعلاً لكن جلب الـ fragment يعيد نفس الـ 301 القديم.

### ✅ الحل

**الخطوة 1 — رتّب المسارات (المسار الخاص قبل العمومي):**
```php
// قبل /thread/{id}/{slug?} مباشرة
Route::get('/thread/{id}/posts-fragment', [\App\Http\Controllers\ThreadController::class, 'postsFragment'])
    ->name('thread.posts-fragment')
    ->middleware(['auth', 'throttle:30,1'])
    ->where('id', '[0-9]+');
```
- أي مسار له `{id}/{slug?}` متبوع بمقطع ثابت يجب تسجيله **قبل** المسار العمومي.

**الخطوة 2 — تجاوز كاش الـ 301 القديم (معامل كسر كاش):**
في `resources/views/thread/show.blade.php`:
```js
const url = '/thread/' + threadId + '/posts-fragment?page=' + page + '&_=' + Date.now();
```
معامل `&_=` يجعل URL جديداً في كل مرة فلا يجد المتصفح نسخة 301 القديمة.

**الخطوة 3 — بعد الرفع:** `php artisan route:clear && php artisan view:clear` + مسح كاش Cloudflare + تحديث صعب للمتصفح (Ctrl+Shift+R).

### الفحص
- `curl -I https://orsozox.com/forums/thread/1/posts-fragment` لضيف → يجب أن يعيد **302** إلى `/login` (وليس 301 كنسي).
- الرد السريع على موضوع من صفحتين يعمل ويعرض الصفحة الثانية.

---

## ❌ المشكلة #6 — إنشاء موضوع جديد يعطي 500 (الواجهة الأمامية ولوحة الإدارة معاً)

### العَرَض
`POST /thread/new` → صفحة "خطأ في الخادم — 500". لوحة Filament (إنشاء موضوع) → 500 أيضاً عبر livewire/update. الردود على المواضيع الموجودة (`Post::create`) كانت تعمل.

### السبب الجذري (مؤكَّد من السجل + بنية الجدول)
جدول `thread` المهاجَر **يختلف عن vBulletin الأصلي**: أعمدة vBulletin القياسية `lastposterid`, `deluserid`, `deldate` **غير موجودة** إطلاقاً، بينما الكود كان يعيّنها عند الإنشاء:

1. `ThreadController::store()` و `Filament CreateThread` يعيّنان `$thread->lastposterid` → `INSERT ... Unknown column 'lastposterid'`.
2. بعد حذفها، ظهر `Unknown column 'deluserid'` (كانت في `$attributes` الافتراضية التي أضفناها للتخمين).
3. العمود `similar` من نوع `varchar(55)` وليس عددياً، والعمود `notes` هو `varchar(250) NOT NULL` **بلا DEFAULT** (كان سيفشل بعدها).

### ✅ الحل — مطابقة النموذج للبنية الفعلية (راجع §10 في مستند المعمارية)

**في `app/Models/Thread.php`** — `$attributes` أصبحت مطابقة لعمود الجدول الحقيقي:
```php
protected $attributes = [
    'firstpostid' => 0,
    'lastpostid' => 0,
    'pollid' => 0,
    'iconid' => 0,
    'prefixid' => '',
    'lastposter' => '',
    'votenum' => 0,
    'votetotal' => 0,
    'attach' => 0,
    'similar' => '',   // varchar — وليس 0
    'notes' => '',     // NOT NULL بلا DEFAULT — لا بد منها
];
```

**في `app/Http/Controllers/ThreadController.php`** — حذف:
```php
$thread->lastposterid = $user->userid;
```

**في `app/Filament/Resources/ThreadResource/Pages/CreateThread.php`** — حذف:
```php
$thread->lastposterid = $post->userid;
```

> 🎯 القاعدة: **لا تخمّن أعمدة جدول vBulletin** — شغّل الأمر 0.2 من مستند الأوامر الجاهزة
> (`SHOW COLUMNS` عبر information_schema) وقارن مع `$attributes`/`$fillable` قبل أي كتابة.
> كل عمود `NOT NULL` بلا `DEFAULT` يجب أن يظهر في `$attributes` أو يُضبط صراحةً.

### الفحص
- إنشاء موضوع من الواجهة الأمامية يعمل ويعرض الموضوع.
- إنشاء موضوع من لوحة Filament يعمل.
- السجل خالٍ من `SQLSTATE[42S22]`.

---

## 🔐 توصيات أمنية إضافية (مكتشفة أثناء الفحص)
1. احذف ملفات التشخيص من الإنتاج: `reset_password.php`, `check*.php`, `public/cc.php`, `public/clear-cache.php`, `public/debug_admin.php`, `public/test.php`, `public/bbcode_test.php`.
2. `public/clear-cache.php` و `cc.php` تسمح بمسح الكاش بدون مصادقة — خطر.
3. تأكد أن `APP_DEBUG=false` في الإنتاج (يمنع تسريب المعلومات في صفحات الخطأ).


