# 📐 بنية المشروع الشاملة — Structure & Architecture

## 🗃️ جداول قاعدة البيانات (vBulletin 3.8)

> **ملاحظة:** المشروع يتصل بجداول vBulletin مباشرةً. لا توجد جداول Laravel مخصصة (ما عدا `sessions` إذا لزم).

---

### جدول `forum` — الأقسام

| العمود | النوع | الوصف |
|--------|-------|-------|
| `forumid` | INT PK | معرف القسم |
| `title` | VARCHAR | عنوان القسم |
| `description` | TEXT | وصف القسم |
| `parentid` | INT | معرف القسم الأب (0 أو -1 = رئيسي) |
| `displayorder` | INT | ترتيب العرض |
| `threadcount` | INT | عدد المواضيع |
| `replycount` | INT | عدد الردود |
| `options` | INT | Bitfield — Bit 1 = مفعّل |
| `password` | VARCHAR | كلمة مرور القسم (فارغة = بدون حماية) |
| `link` | VARCHAR | رابط خارجي (إذا القسم redirect) |

**العلاقات:**
```
forum.parentid → forum.forumid (self-referential, القسم الأب)
forum.forumid → thread.forumid (المواضيع في القسم)
forum.forumid → forumpermission.forumid (صلاحيات القسم)
```

---

### جدول `thread` — المواضيع

| العمود | النوع | الوصف |
|--------|-------|-------|
| `threadid` | INT PK | معرف الموضوع |
| `title` | VARCHAR | عنوان الموضوع |
| `forumid` | INT FK | القسم التابع له |
| `postuserid` | INT FK | معرف كاتب الموضوع |
| `postusername` | VARCHAR | اسم الكاتب (للزوار) |
| `dateline` | INT | تاريخ الإنشاء (Unix timestamp) |
| `lastpost` | INT | تاريخ آخر رد (Unix timestamp) |
| `lastposterid` | INT | معرف آخر من رد |
| `views` | INT | عدد المشاهدات |
| `replycount` | INT | عدد الردود |
| `open` | TINYINT | 1=مفتوح، 0=مغلق |
| `visible` | TINYINT | 1=مرئي، 0=محذوف/معلق |
| `firstpostid` | INT | معرف أول مشاركة |

**العلاقات:**
```
thread.forumid    → forum.forumid
thread.postuserid → user.userid
thread.threadid   → post.threadid
thread.firstpostid → post.postid
```

---

### جدول `post` — المشاركات والردود

| العمود | النوع | الوصف |
|--------|-------|-------|
| `postid` | INT PK | معرف المشاركة |
| `threadid` | INT FK | الموضوع التابع له |
| `userid` | INT FK | معرف الكاتب |
| `username` | VARCHAR | اسم الكاتب |
| `dateline` | INT | تاريخ الكتابة (Unix timestamp) |
| `pagetext` | MEDIUMTEXT | نص المشاركة (BBCode) |
| `visible` | SMALLINT | 1=مرئي، 0=محذوف |
| `ipaddress` | VARCHAR | عنوان IP |

**العلاقات:**
```
post.threadid → thread.threadid
post.userid   → user.userid
post.postid   → attachment.postid
```

---

### جدول `user` — الأعضاء

| العمود | النوع | الوصف |
|--------|-------|-------|
| `userid` | INT PK | معرف العضو |
| `username` | VARCHAR | اسم المستخدم |
| `email` | VARCHAR | البريد الإلكتروني |
| `password` | VARCHAR(32) | MD5(MD5(pass)+salt) |
| `salt` | VARCHAR(30) | الـ Salt لكلمة المرور |
| `usergroupid` | INT | معرف المجموعة الافتراضية |
| `joindate` | INT | تاريخ التسجيل (Unix timestamp) |
| `posts` | INT | إجمالي عدد المشاركات |
| `lastvisit` | INT | آخر زيارة |
| `lastactivity` | INT | آخر نشاط |

**مجموعات المستخدمين `usergroupid`:**
```
1 = Unregistered (زائر)
2 = Registered (عضو)
5 = Moderators (مشرف)
6 = Super Moderators (مشرف عام)
7 = Administrators (مدير)
```

---

### جدول `forumpermission` — صلاحيات الأقسام

| العمود | النوع | الوصف |
|--------|-------|-------|
| `forumpermissionid` | INT PK | معرف السجل |
| `forumid` | INT FK | القسم |
| `usergroupid` | INT | مجموعة المستخدمين |
| `forumpermissions` | INT | Bitfield الصلاحيات |

**Bitfield الصلاحيات الأهم:**
```
Bit 1  (1)  = canview      — يمكن رؤية القسم
Bit 2  (2)  = canreply     — يمكن الرد
Bit 4  (4)  = canpost      — يمكن إنشاء مواضيع
Bit 8  (8)  = canpostattachment — يمكن رفع مرفقات
Bit 64 (64) = canview_threads   — يمكن رؤية المواضيع
```

**منطق التحقق:**
```
لا يوجد سجل → مسموح (افتراضي vBulletin)
forumpermissions & 1 = 1 → مسموح
forumpermissions & 1 = 0 → محجوب
```

---

### جدول `attachment` — المرفقات

| العمود | النوع | الوصف |
|--------|-------|-------|
| `attachmentid` | INT PK | معرف المرفق |
| `postid` | INT FK | المشاركة التابع لها |
| `userid` | INT | رافع الملف |
| `filename` | VARCHAR | اسم الملف |
| `filesize` | INT | الحجم بالبايت |
| `extension` | VARCHAR | امتداد الملف |
| `dateline` | INT | تاريخ الرفع |

---

## 🏗️ بنية Models والعلاقات

```
Forum
 ├── parent() → Forum (القسم الأب)
 ├── children() → Forum[] (الأقسام الفرعية)  
 ├── threads() → Thread[]
 └── permissions() → ForumPermission[]

Thread
 ├── forum() → Forum
 ├── author() → User
 ├── posts() → Post[]
 └── firstPost() → Post

Post
 ├── thread() → Thread
 ├── author() → User
 └── attachments() → Attachment[]

User
 ├── threads() → Thread[]
 └── posts() → Post[]

ForumPermission
 └── [static] canView(forumid, usergroupid): bool
```

---

## 🔄 تدفق البيانات (Data Flow)

### زيارة موضوع (Thread Page)

```
Browser Request
  ↓
.htaccess (mod_rewrite)
  ↓ Route: GET /thread/{id}/{slug?}
Router (routes/web.php)
  ↓
ThreadController::show($id, $slug)
  ↓
1. Thread::with(['forum','author'])->visible()->findOrFail($id)
2. ForumPermission::canView($thread->forumid, $usergroupId)
   - إذا محجوب → return response()->view('errors.forbidden')
3. Redirect إذا slug خاطئ (301)
4. $thread->posts()->visible()->chronological()->paginate(15)
5. كل مشاركة: BBCodeParser::parse($post->pagetext) → HTML
  ↓
thread/show.blade.php
  ↓
layouts/app.blade.php (القالب الرئيسي)
  ↓
Browser Response
```

### تسجيل الدخول

```
POST /login
  ↓
AuthController::login()
  ↓
Auth::attempt(['username' => $username, 'password' => $password])
  ↓
VBulletinUserProvider::validateCredentials()
  ↓
md5(md5($password) . $user->salt) === $user->password ?
  ↓ نعم
Auth::login($user) → Session
  ↓
Redirect to home
```

---

## 📁 Controllers — المسؤوليات

### `HomeController`
- يُحمِّل أحدث 12 موضوع + أكثر 6 مشاهدةً + الأقسام الرئيسية
- Cache منفصل لكل `usergroupid` (مفتاح: `home_forums_{usergroupId}`)
- مدة Cache: Forums=30دق، Threads=10دق، Stats=60دق

### `ForumController`
- يُحمِّل القسم من Cache (30دق) → يتحقق من الصلاحية → يعرض المواضيع
- يُعيد توجيه 301 إذا كان الـ slug خاطئاً

### `ThreadController`  
- يُحمِّل الموضوع → يتحقق من صلاحية قسمه → يعرض المشاركات مع BBCode
- يزيد عداد المشاهدات (`views++`)

### `SitemapController`
- `index()`: يُنشئ Sitemap Index مُقسَّم حسب عدد المواضيع
- `forums()`: Sitemap الأقسام (يتجدد كل 24 ساعة)
- `threads($page)`: Sitemap المواضيع بـ 1000 موضوع/صفحة + `lastmod` حقيقي

### `RedirectController`
- يُحوِّل `showthread.php?t=ID` → يجلب الـ slug → يُعيد 301

---

## 🎨 نظام CSS (Light/Dark Mode)

ملف `public/css/app.css` يستخدم CSS Variables:

```css
/* Light Mode (افتراضي) */
:root {
  --bg-primary: #ffffff;
  --text-primary: #1a1a2e;
  --accent-color: #7c3aed;
}

/* Dark Mode */
[data-theme="dark"] {
  --bg-primary: #0f0f1a;
  --text-primary: #e8eaf0;
}
```

يُخزَّن التفضيل في `localStorage`:
```javascript
localStorage.setItem('theme', 'dark'); // أو 'light'
document.documentElement.setAttribute('data-theme', theme);
```

---

## ⚙️ BBCodeParser — المحلل

`app/Helpers/BBCodeParser.php` يُحوِّل BBCode إلى HTML آمن:

```
[b]نص[/b]          → <strong>نص</strong>
[i]نص[/i]          → <em>نص</em>  
[url=رابط]نص[/url] → <a href="...">نص</a>
[img]رابط[/img]    → <img src="...">
[quote]نص[/quote]  → <blockquote>نص</blockquote>
[color=red]...[/color] → <span style="color:red">
[size=3]...[/size] → <span style="font-size:...">
```

جميع الروابط تمر عبر `RedirectorController` لمنع XSS من الروابط الخارجية.

---

## 🔑 SeoHelper — المساعد

| الدالة | الوصف |
|--------|-------|
| `title($title, $section)` | `عنوان - قسم \| اسم الموقع` |
| `description($text, $length)` | يُنظّف BBCode + يقطع عند 160 حرفاً |
| `openGraph($data)` | يُولّد وسوم `og:*` |
| `schemaArticle($data)` | JSON-LD لنوع `DiscussionForumPosting` |
| `schemaBreadcrumb($items)` | JSON-LD لـ `BreadcrumbList` |

---

## 📌 ملاحظات تقنية مهمة

1. **لا migrations** — كل الجداول موجودة مسبقاً في قاعدة بيانات vBulletin
2. **timestamps = false** — كل Model يملك `public $timestamps = false`
3. **dateline** — كل التواريخ مخزنة كـ Unix timestamp (INT) وتُحوَّل بـ accessor
4. **BBCode** — المحتوى مخزَّن بـ BBCode في `pagetext`، يُحوَّل عند العرض فقط
5. **Cache Driver = file** — مناسب للاستضافة المشتركة (بدون Redis)
6. **Filament Admin** — يصل إليه فقط من `usergroupid` في [5, 6, 7]
