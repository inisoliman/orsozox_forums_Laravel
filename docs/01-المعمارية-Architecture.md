# 🏗️ معمارية النظام — Architecture

شرح تفصيلي لكيفية عمل المنتدى والترابط بين أجزائه.

---

## 1) قاعدة البيانات والاتصال

`config/database.php`:
- اتصال واحد `mysql`.
- **بدون بادئة جداول** (`'prefix' => ''`) → جداول vBulletin تُستدعى بأسمائها المجردة: `user`, `forum`, `thread`, `post`, `session`.
- `'strict' => false` لتحمّل البنية القديمة (legacy schema) لـ vBulletin.

الجداول تنقسم إلى نوعين:
| النوع | أمثلة | ملاحظة |
|------|-------|--------|
| جداول vBulletin الأصلية | `user`, `forum`, `thread`, `post`, `session`, `forumpermission` | **لا تُعدَّل بنيتها أبداً** |
| جداول Laravel الحديثة | `news_tickers`, `site_settings`, `image_cache`, `email_campaigns`, `email_subscribers`, `email_logs`, `jobs`, `cache` | أُنشئت عبر migrations |

---

## 2) المصادقة بنظام vBulletin (مهم جداً)

vBulletin 3.8 لا يستخدم تشفير Laravel (bcrypt). يستخدم:
```
hash = md5( md5(plain_password) + salt )
```

كيف رُبط هذا في Laravel:
1. `config/auth.php`: الحارسان `web` و `api` يستخدمان provider اسمه `vbulletin`.
2. `app/Providers/VBulletinAuthServiceProvider.php` يسجّل الـ driver:
   ```php
   Auth::provider('vbulletin', fn() => new VBulletinUserProvider());
   ```
3. `app/Auth/VBulletinUserProvider.php` يطبّق `UserProvider`:
   - `retrieveById` → `User::find($id)`
   - `validateCredentials` → يستدعي `User::verifyPassword()`
4. `app/Models/User.php::verifyPassword()`:
   ```php
   $hash = md5(md5($password) . $this->salt);
   return $hash === $this->password;
   ```

**نقاط مهمة في `User`:**
- `$primaryKey = 'userid'`، `$timestamps = false`.
- `getIsAdminAttribute()` → المستخدم أدمن إذا كانت `usergroupid` ضمن `config('forum.admin_usergroup_ids')` = `[5,6,7]`.
- `canAccessPanel()` → يسمح بدخول لوحة Filament للأدمن فقط.
- لا يوجد `remember token` (vBulletin لا يدعمه — الدوال تُرجع قيماً فارغة).
- لا يوجد نظام "نسيت كلمة المرور" مطلقاً (لا توجد أي مسارات Password reset).

> أداة طوارئ: `reset_password.php` في الجذر تعيد تعيين كلمة مرور المستخدم رقم 2 يدوياً عبر PDO. **يجب حذفها من الإنتاج** (ثغرة أمنية).

---

## 3) النماذج (Models) وربطها بجداول vBulletin

| Model | الجدول | المفتاح | ملاحظات |
|-------|--------|---------|---------|
| `User` | `user` | `userid` | تحقق كلمة المرور md5+salt |
| `Forum` | `forum` | `forumid` | `scopeActive` يستخدم بِت الخيارات `options & 1`؛ `url` accessor عبر `route('forum.show')` |
| `Thread` | `thread` | `threadid` | `scopeVisible`؛ `url` accessor؛ `last_post_date`؛ `$attributes` يعطي افتراضيات مطابقة للبنية الفعلية (انظر §10) |
| `Post` | `post` | `postid` | محتوى BBCode → يُحوَّل عبر `BBCodeParser` |
| `Session` | `session` | `sessionhash` (نصي) | `getLastActivityAttribute` يحوّل لـ Carbon ⚠️ |
| `ForumPermission` | `forumpermission` | — | صلاحيات المجموعات على الأقسام |

**جداول حديثة:** `NewsTicker`, `SiteSetting`, `ImageCache`, `EmailCampaign`, `EmailSubscriber`, `EmailLog`.

---

## 4) الـ Middleware وترتيبها (`bootstrap/app.php`)

```
web (prepend):  BotTrafficProtectionMiddleware     ← يعمل أولاً (جدار حماية البوتات)
web (append):   UpdateLegacySession                ← يكتب جلسة المتصفح في جدول vB session
                SecurityHeadersMiddleware
                HtmlMinifyMiddleware
                PerformanceMonitorMiddleware
```

- **`BotTrafficProtectionMiddleware`** → يستدعي `FirewallDecisionService` → `TrafficClassificationService`.
  - يصنّف الزائر (محرك بحث موثّق / زاحف AI / سكرابر عدواني / إنسان...).
  - يحجب (429) الزوار المشبوهين عند تجاوز السكور أو معدل الطلبات.
  - **محركات البحث الموثّقة (Google/Bing مع تحقق reverse-DNS) تُسمح دائماً.**
- **`UpdateLegacySession`** → يحدّث/ينشئ صفّاً في جدول `session` لكل زائر بشري.
  - ⚠️ **يتخطّى البوتات عمداً** (السطر 44-48) → لذلك البوتات لا تظهر في "المتواجدون الآن" (مشكلة #4).

---

## 5) خريطة المسارات (Routes) — `routes/web.php`

| المسار | الـ Controller | الاسم |
|--------|----------------|-------|
| `/` | HomeController@index | home |
| `/about`, `/editorial-policy`, `/privacy-policy`, `/contact` | PageController | page.* |
| `/forum/{id}/{slug?}` | ForumController@show | forum.show |
| `/thread/new` | ThreadController@create | thread.create (auth) |
| `/thread/new` (POST) | ThreadController@store | thread.store (auth) |
| `/thread/{id}/posts-fragment` | ThreadController@postsFragment | thread.posts-fragment (auth + throttle:30,1) |
| `/thread/{id}/{slug?}` | ThreadController@show | thread.show |
| `/thread/{id}/reply` (POST) | ReplyController@store | thread.reply (auth) |
| `/user/{id}` | UserController@show | user.show |
| `/search`, `/search/suggest` | SearchController | search, search.suggest |
| `/posts/{postid}` | PostController@show | post.show |
| `/login`, `/logout` | AuthController | login, logout |
| `/register` | redirect→login (مغلق) | register |
| `/sitemap.xml` | SitemapController@index | sitemap |
| `/sitemap-forums.xml` | SitemapController@forums | sitemap.forums |
| `/sitemap-threads-{page}.xml` | SitemapController@threads | sitemap.threads |
| `/online-users` | OnlineUsersController@index | online.users (auth) |
| `/unsubscribe/{hash}` | UnsubscribeController | email.unsubscribe |
| `/newsletter/subscribe` | NewsletterController | newsletter.subscribe |
| `/showthread.php`, `/forumdisplay.php`, `/member.php`, `/archive/...`, `/tags.php`, `/f{id}...` | RedirectController | تحويلات 301 لروابط vBulletin القديمة (مهمة للسيو) |

> ملاحظة مهمة: كل المسارات معرّفة عند **الجذر** (`/sitemap.xml`)، لكن `APP_URL` يحتوي `/forums`، فتولّد `route()` روابط مثل `https://orsozox.com/forums/sitemap.xml`. هذا يعمل لأن التطبيق يُخدَم من داخل مجلد `/forums`. (مرتبط بمشكلة الخرائط #2).

> ⚠️ **قاعدة ترتيب المسارات:** أي مسار بصيغة `/thread/{id}/<مقطع ثابت>` (مثل `posts-fragment`)
> يجب تسجيله **قبل** `/thread/{id}/{slug?}` وإلا سيعامله لارافيل كـ slug ويعيد 301 كنسي (مشكلة #5).

---

## 6) لوحة التحكم Filament

- لوحة الإدارة في `app/Filament/` (Resources + Pages + Widgets).
- الدخول مقصور على الأدمن عبر `User::canAccessPanel()`.
- تُدير: الأعضاء، المواضيع، الأقسام، النشرة البريدية (Email Campaigns)، الإعدادات، شريط الأخبار.

---

## 7) نظام الإيميلات والطابور (Jobs)

التدفّق:
```
لوحة Filament (إنشاء حملة) → SendCampaignEmailJob (ShouldQueue) → الطابور → Mail::html(...) عبر SMTP
```
- `SendCampaignEmailJob` و `ValidateEmailJob` و `ScanImagesJob` كلها `ShouldQueue` → تعتمد على إعداد `QUEUE_CONNECTION`.
- على الاستضافة المشتركة لا يوجد `queue:work` دائم → **يجب** أن يكون `QUEUE_CONNECTION=sync` أو تشغيل الطابور عبر cron. (مرتبط بمشكلة الإيميل #1).
- حدود الإرسال مدمجة في الـ Job (50/ساعة، 200/يوم) لتناسب Hostinger.

---

## 8) نظام SEO والخرائط (Sitemap)

- الخرائط **مكتوبة يدوياً** في `SitemapController` (مكتبة spatie غير مستخدمة فعلياً).
- `index()` → فهرس `<sitemapindex>` يشير إلى `sitemap-forums.xml` + خرائط المواضيع المقسّمة بصفحات (1000 موضوع/صفحة).
- `forums()` → الأقسام + الصفحات الثابتة (مع كاش يوم كامل).
- `threads($page)` → المواضيع المرئية مرتّبة بالأحدث.
- `users()` → تُرجع 404 عمداً (ملفات الأعضاء noindex).

---

## 9) ملفات تشخيص في الجذر و public/ (تنبيه أمني)

ملفات مكشوفة يجب **حذفها/تأمينها** في الإنتاج:
`check.php`, `check_debug.php`, `check_login.php`, `reset_password.php`,
`public/cc.php`, `public/clear-cache.php`, `public/debug_admin.php`, `public/test.php`, `public/bbcode_test.php`.
هذه تكشف معلومات حساسة أو تسمح بمسح الكاش/تغيير كلمات المرور دون مصادقة.

---

## 10) بنية جدول `thread` الفعلية (من information_schema — 2026-08-18)

> ⚠️ **الدرس الأهم من مشكلة #6:** الجدول المهاجَر يفتقد أعمدة vBulletin القياسية
> `lastposterid`, `deluserid`, `deldate` — وكان الكود يعيّنها → `Unknown column` 500.
> `similar` عمود **نصي** `varchar(55)`. **قبل أي كتابة في جداول vBulletin: شغّل أمر 0.2 من**
> `docs/05-أوامر-جاهزة` وقارن الأعمدة مع النموذج — لا تخمّن البنية أبداً.

| العمود | النوع | الافتراضي | ملاحظة |
|--------|-------|-----------|--------|
| `threadid` | int(10) unsigned | auto_increment | PK |
| `title` | varchar(250) | `''` | |
| `prefixid` | varchar(25) | `''` | |
| `firstpostid` | int(10) unsigned | 0 | |
| `lastpostid` | int(10) unsigned | 0 | |
| `lastpost` | int(10) unsigned | 0 | |
| `forumid` | smallint(5) unsigned | 0 | |
| `pollid` | int(10) unsigned | 0 | |
| `open` | smallint(6) | 0 | |
| `replycount` | int(10) unsigned | 0 | |
| `hiddencount` | int(10) unsigned | 0 | |
| `deletedcount` | int(10) unsigned | 0 | |
| `postusername` | varchar(100) | `''` | |
| `postuserid` | int(10) unsigned | 0 | |
| `lastposter` | varchar(100) | `''` | |
| `dateline` | int(10) unsigned | 0 | |
| `views` | int(10) unsigned | 0 | |
| `iconid` | smallint(5) unsigned | 0 | |
| `notes` | varchar(250) | `''` | |
| `visible` | smallint(6) | 0 | |
| `sticky` | smallint(6) | 0 | |
| `votenum` | smallint(5) unsigned | 0 | |
| `votetotal` | smallint(5) unsigned | 0 | |
| `attach` | smallint(5) unsigned | 0 | |
| `similar` | varchar(55) | `''` | **نصي وليس عددياً** |
| `taglist` | mediumtext | NULL | nullable |
| `importthreadid` | bigint(20) | 0 | |
| `importforumid` | bigint(20) | 0 | |
| `vbseo_linkbacks_no` | int(10) unsigned | 0 | |
| `vbseo_likes` | int(10) unsigned | 0 | |
| `dbtech_thanks_requiredbuttons_content` | int(10) unsigned | 0 | |
| `dbtech_thanks_requiredbuttons_attach` | int(10) unsigned | 0 | |

> ℹ️ **تصحيح:** كل أعمدة `thread` **لها قيم افتراضية** في القاعدة (فحص `information_schema`
> الكامل بتاريخ 2026-08-18). لذلك كتلة `$attributes` في `app/Models/Thread.php`
> طبقة دفاعية زائدة (قيمها مطابقة للافتراضات) — تركها اختياري لكنه لا يضر.
> الشرط الوحيد الحقيقي: **لا تعيّن أبداً أعمدة غير موجودة** (`lastposterid`/`deluserid`/`deldate`).
> بنية بقية الجداول الأساسية في `docs/06-بنية-قاعدة-البيانات-Database-Schema.md`.
