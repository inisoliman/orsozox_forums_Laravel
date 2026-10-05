# 🗄️ بنية قاعدة البيانات الكاملة — Database Schema

> مصدر المعلومات: استعلام `information_schema.COLUMNS` عبر phpMyAdmin بتاريخ **2026-08-18**
> (الخادم `auth-db1694.hstgr.io`، قاعدة `u626751827_for11`، خادم `MariaDB`).
> ‏**188 جدولاً** و **1563 عموداً** إجمالاً. الترميز: `utf8mb4_unicode_ci`.

---

## 1) نظرة عامة

- القاعدة هي **قاعدة vBulletin 3.8 القديمة كما هي** + جداول Laravel حديثة أُضيفت للمشروع.
- محركات التخزين: `InnoDB` للغالبية العظمى، و **`MEMORY`** للجداول المؤقتة
  (`session`, `cpsession`, `aaggregate_temp_*` — تُمحى عند إعادة تشغيل الخادم).
- **قاعدة ذهبية:** لا تعدّل بنية الجداول الأصلية أبداً. لا تخمّن الأعمدة —
  شغّل الأمر 0.2 من `05-أوامر-جاهزة` وقارن قبل أي كود.

### أكبر الجداول حجماً

| الجدول | عدد الصفوف | الدور |
|--------|-----------|-------|
| `post` | 826,442 | المشاركات |
| `thread` | 76,656 | المواضيع |
| `user` | 128,460 | المستخدمون |
| `word` | 1,138,783 | فهرس البحث |
| `email_subscribers` | 139,583 | النشرة البريدية (جديد) |
| `postindex` | ~ | فهرس النصوص للبحث |
| `tachyforumpostcache` | ~ | كاش المشاركات (إضافة TachyForum) |

---

## 2) فهرس الجداول حسب الفئة

### أ. الجداول الأساسية التي يستخدمها كود Laravel
| الجدول | الوصف |
|--------|-------|
| `user` | المستخدمون (المصادقة بـ md5+salt) |
| `forum` | الأقسام |
| `thread` | المواضيع |
| `post` | المشاركات |
| `session` | الجلسات الفعلية (MEMORY) — يعتمد عليها عداد "المتواجدون" |
| `forumpermission` | صلاحيات المجموعة على القسم |
| `usergroup` | المجموعات وصلاحياتها |

### ب. جداول المحتوى والتصنيف
`access`, `announcement`, `attachment`, `calendar`, `custombbcode`, `event`,
`infraction`, `poll`, `pollvote`, `reputation`, `reputationlevel`, `smilie`,
`tag`, `tagthread`, `tagsearch`, `threadrate`, `threadredirect`, `threadviews`,
`visitormessage`, `picture`, `picturecomment`, `groupmessage`, `socialgroup`,
`socialgroupmember`, `socialgroupcategory`, `discussion`, `discussionread`

### ج. جداول التخصيص والصلاحيات
`style`, `template`, `templatehistory`, `phrase`, `phrasetype`, `language`,
`notice`, `noticecriteria`, `usertitle`, `ranks`, `profilefield`,
`profileblockprivacy`, `profilevisitor`, `usercss`, `avatar`, `customavatar`,
`sigpic`, `sigparsed`, `prefix`, `prefixpermission`, `forumprefixset`

### د. جداول الأدمن والمراقبة
`admin`, `adminlog`, `administrator`, `adminhelp`, `moderator`, `moderatorlog`,
`userban`, `userchangelog`, `usernote`, `userpromotion`, `useractivation`,
`strikes`, `spamlog`, `deletionlog`, `postedithistory`, `postlog`, `posthash`,
`ipaddress`, `humanverify`, `hvanswer`, `hvquestion`, `cpsession`

### هـ. جداول الرسائل الخاصة والإشعارات
`pm`, `pmtext`, `pmreceipt`, `subscription`, `subscriptionlog`,
`subscribethread`, `subscribeforum`, `subscribeevent`, `subscribediscussion`,
`subscribegroup`, `userlist`

### و. جداول vBulletin/الإضافات الأخرى
`datastore`, `setting`, `settinggroup`, `plugin`, `product`, `mailqueue`,
`rssfeed`, `rsslog`, `stats`, `upgradelog`, `upgradefixlist`, `impexerror`,
`bookmarksite`, `externalcache`, `paymentapi`, `paymentinfo`, `search`,
`word`, `postindex`, `postparsed`, `threadread`, `forumread`, `vbfields`,
`userfield`, `usertextfield`, `forum`, `bbcode`, `moderation`, `cron`,
`session`, `phrase`, `event`, `calendarpermission`

### ز. إضافات سابقة/خاصة بالمنتدى
`advanced_forums_layout`, `ainadsense`, `ncode_imageresizer*` (ضمن user),
`tachyforumpostcache`, `tachyforumposthash`, `tachyforumpostlog`,
`vbseo_likes*` (ضمن thread/user), `dbtech_thanks_*`, `quiz`, `quizquestion`,
`aaggregate_temp_1h/1d/7d` (MEMORY)

### ح. جداول Laravel الحديثة (migrations)
| الجدول | الوصف |
|--------|-------|
| `news_tickers` | شريط الأخبار |
| `site_settings` | الإعدادات |
| `image_cache` | كاش الصور |
| `email_campaigns` | الحملات البريدية |
| `email_subscribers` | المشتركون (139,583 صفاً) |
| `email_logs` | سجل الإرسال |
| `jobs` / `failed_jobs` | الطابور (⚠️ العالق هنا سبب مشكلة الإيميل #1) |
| `migrations` | سجل الهجرات |
| `thread_keywords` | كلمات مفتاحية للمواضيع |

---

## 3) بنية الجداول الأساسية التفصيلية

### 3.1 `user` (المفتاح `userid`)

| العمود | النوع | ملاحظة |
|--------|-------|--------|
| `userid` | int(10) unsigned AI | PK |
| `usergroupid` | smallint(5) unsigned | المجموعة الرئيسية — المستخدم أدمن إذا كانت ضمن `[5,6,7]` |
| `membergroupids` | varchar(250) | مجموعات إضافية مفصولة بفواصل |
| `displaygroupid` | smallint(5) unsigned | |
| `username` | varchar(100) | |
| `password` | char(32) | `md5(md5(كلمة السر) + salt)` |
| `passworddate` | date | |
| `email` | char(100) | |
| `salt` | char(30) | ملح التشفير |
| `joindate` | int(10) unsigned | UNIX timestamp |
| `lastvisit` | int(10) unsigned | |
| `lastactivity` | int(10) unsigned | ⚠️ accessor يحوّله لـ Carbon (مشكلة #3) |
| `lastpost` | int(10) unsigned | |
| `lastpostid` | int(10) unsigned | في `user` (عبر عمود `lastpostid`) — و**موجود أيضاً في `thread`** (راجع docs/01 §10) |
| `posts` | int(10) unsigned | |
| `reputation` | int(10) | |
| `options` | int(10) unsigned | بِتات خيارات (افتراضي 15) |
| `birthday` | varchar(10) | |
| `ipaddress` | varchar(50) | |
| `languageid` | smallint(5) unsigned | |
| `importuserid` | bigint(20) | بيانات الهجرة |
| `vbseo_likes_in/out/unread` | int(10) unsigned | إضافة vbSEO |
| `dbtech_thanks_*` | int(10) unsigned | إضافة Thanks |

> لا يوجد `lastposterid` في `user`. كل الأعمدة لها قيم افتراضية (غالباً 0 أو '').

### 3.2 `forum` (المفتاح `forumid`)

| العمود | النوع | ملاحظة |
|--------|-------|--------|
| `forumid` | smallint(5) unsigned AI | PK |
| `title` / `title_clean` | varchar(100) | |
| `description` / `description_clean` | text | |
| `options` | int(10) unsigned | `options & 1` = مفعّل (يستخدمه `scopeActive`) |
| `showprivate` | smallint(6) | |
| `displayorder` | smallint(6) | |
| `replycount` / `threadcount` | int(10) unsigned | |
| `lastpost` | int(10) unsigned | |
| `lastposter` | varchar(100) | |
| `lastpostid` | int(10) unsigned | |
| `lastthread` / `lastthreadid` | varchar/int | |
| `parentid` | smallint(5) unsigned | قسم أب |
| `parentlist` | text | قائمة الأجداد مفصولة بفواصل |
| `childlist` | text | قائمة الأطفال |
| `password` | varchar(32) | |
| `link` | varchar(250) | |
| `defaultsortfield` / `defaultsortorder` | varchar | `lastpost` / `desc` |
| `imageprefix` | varchar(100) | |
| `importforumid` / `importcategoryid` | bigint | بيانات الهجرة |
| `advanced_forums_layout` | int | إضافة |
| `newthread_button` | text | إضافة |

> لا يوجد عمود `active` — تفعيل القسم عبر بِت `options`.

### 3.3 `thread` (المفتاح `threadid`) — **36 عموداً**
بنيتها كاملة موثّقة في `01-المعمارية-Architecture.md` (§10).
خلاصة: تفتقد `lastposterid`/`deluserid`/`deldate`، و`similar` نصي `varchar(55)`،
وكل الأعمدة لها قيم افتراضية.

### 3.4 `post` (المفتاح `postid`)

| العمود | النوع | ملاحظة |
|--------|-------|--------|
| `postid` | int(10) unsigned AI | PK |
| `threadid` | int(10) unsigned | |
| `parentid` | int(10) unsigned | المشاركة الأم (للاقتباس/الرد المتعدد) |
| `username` | varchar(100) | |
| `userid` | int(10) unsigned | |
| `title` | varchar(250) | |
| `dateline` | int(10) unsigned | UNIX timestamp |
| `pagetext` | mediumtext | المحتوى **بصيغة BBCode** |
| `allowsmilie` | smallint(6) | |
| `showsignature` | smallint(6) | |
| `ipaddress` | varchar(50) | |
| `iconid` | smallint(5) unsigned | |
| `visible` | smallint(6) | |
| `attach` | smallint(5) unsigned | |
| `infraction` | int(10) unsigned | |
| `reportthreadid` | int(10) unsigned | |
| `importthreadid` / `importpostid` | bigint | بيانات الهجرة |
| `ame_flag` | smallint(6) | إضافة AutoMediaEmbed |

### 3.5 `session` (المفتاح `sessionhash`) — **محرك MEMORY**

| العمود | النوع | ملاحظة |
|--------|-------|--------|
| `sessionhash` | char(32) | PK |
| `userid` | int(10) unsigned | |
| `host` | varchar(250) | |
| `idhash` | char(32) | |
| `lastactivity` | int(10) unsigned | |
| `location` | text | |
| `useragent` | varchar(250) | |
| `styleid` | smallint(5) unsigned | |
| `languageid` | smallint(5) unsigned | |
| `loggedin` | smallint(6) | |
| `inforum` / `inthread` / `incalendar` | int(10) unsigned | |
| `badlocation` | smallint(6) | |
| `bypass` | smallint(6) | |
| `profileupdate` | smallint(6) | |

> ⚠️ **محرك MEMORY:** كل الصفوف تُمحى عند إعادة تشغيل الخادم — لا تخزن فيه أي شيء دائم.
> عداد "المتواجدون الآن" يعتمد عليه (والبوتات لا تُكتب فيه عمداً — مشكلة #4).

### 3.6 `forumpermission` (المفتاح `forumpermissionid`)
| العمود | النوع | ملاحظة |
|--------|-------|--------|
| `forumpermissionid` | int(10) unsigned AI | PK |
| `forumid` | smallint(5) unsigned | |
| `usergroupid` | smallint(5) unsigned | |
| `forumpermissions` | int(10) unsigned | بِتات الصلاحيات |

### 3.7 `usergroup` (المفتاح `usergroupid`)
| العمود | النوع | ملاحظة |
|--------|-------|--------|
| `usergroupid` | smallint(5) unsigned AI | PK |
| `title` | varchar(100) | |
| `description` | text | |
| `usertitle` | varchar(250) | |
| `ispublicgroup` | smallint(6) | |
| `forumpermissions` | int(10) unsigned | بِتات صلاحيات الأقسام |
| `pmpermissions` | int(10) unsigned | |
| `genericpermissions` / `genericpermissions2` | int(10) unsigned | |
| `genericoptions` | int(10) unsigned | |
| `signaturepermissions` | int(10) unsigned | |
| `attachlimit` | int(10) unsigned | |
| `sigmaxchars` / `sigmaxlines` | int | |
| `importusergroupid` | bigint | بيانات الهجرة |

---

## 4) ملاحظات مهمة للتطوير

1. **لا تقصد `lastposterid` أبداً في `thread`** — غير موجود (موجود فقط في `user.lastpostid`). سبب المشكلة #6.
2. **`pagetext` في `post` بصيغة BBCode** وليست HTML — تُحوَّل عبر `BBCodeParser` عند العرض.
3. **`dateline`/`lastactivity`/`joindate`/`lastpost` أرقام UNIX** — تُحوَّل إلى Carbon في النماذج.
4. **`session` و`cpsession` بمحرك MEMORY** — تُمحى مع كل إعادة تشغيل للخادم.
5. **`user.password` = `md5(md5(pass) . salt)`** — لا يُطابق bcrypt أبداً.
6. **حقول الإضافات** (`vbseo_*`, `dbtech_*`, `tachy*`, `advanced_forums_*`) قيم افتراضية صفرية/فارغة — لا تفرضها في الكود.
7. **جداول Laravel الحديثة** وحدها التي يجوز تعديلها عبر migrations — جداول vBulletin تُقرأ وتُكتب فقط.
8. **جدول `jobs`** هو طابور قاعدة البيانات — إذا لم يوجد queue worker عالق الإيميلات فيه (مشكلة #1).