# حزمة رفع منظومة الإشراف للاختبار Online

هذه الوثيقة هي manifest الرفع. لا ترفع المشروع كاملاً ولا ترفع الأسرار أو قاعدة البيانات داخل حزمة الملفات.

## الملفات المطلوبة

### الخدمات والدعم

```text
app/Services/ModerationPermissionService.php
app/Services/ModerationActionService.php
app/Services/ModerationService.php
app/Services/ModerationAuditService.php
app/Services/ForumCounterService.php
app/Services/ThreadCounterService.php
app/Support/VBulletinModeratorPermissions.php
```

### النماذج والسياسات

```text
app/Models/User.php
app/Models/Thread.php
app/Models/Post.php
app/Models/Forum.php
app/Models/ModeratorLog.php
app/Models/ModeratorAssignment.php
app/Policies/ThreadPolicy.php
app/Policies/PostPolicy.php
```

### المتحكمات والمسارات

```text
app/Http/Controllers/ModerationController.php
app/Http/Controllers/ModerationBulkController.php
app/Http/Controllers/ForumController.php
app/Http/Controllers/ThreadController.php
app/Http/Controllers/ReplyController.php
app/Http/Controllers/UserController.php
app/Http/Controllers/Api/ThreadActionController.php
app/Http/Controllers/Api/ThreadEditController.php
app/Http/Controllers/Api/PostEditController.php
routes/web.php
```

### موارد Filament

```text
app/Filament/Resources/ThreadResource.php
app/Filament/Resources/PostResource.php
app/Filament/Resources/PendingThreadResource.php
app/Filament/Resources/PendingPostResource.php
app/Filament/Resources/DeletedThreadResource.php
app/Filament/Resources/DeletedPostResource.php
app/Filament/Resources/ModerationLogResource.php
app/Filament/Resources/ThreadResource/Pages/EditThread.php
app/Filament/Resources/PostResource/Pages/EditPost.php
app/Filament/Resources/DeletedThreadResource/Pages/ListDeletedThreads.php
app/Filament/Resources/DeletedPostResource/Pages/ListDeletedPosts.php
app/Filament/Resources/ModerationLogResource/Pages/ListModerationLogs.php
```

### القوالب والاختبارات والوثائق

```text
resources/views/forum/show.blade.php
resources/views/thread/show.blade.php
resources/views/thread/partials/post.blade.php
resources/views/filament/forms/components/duplicate-checker.blade.php
resources/views/layouts/app.blade.php
resources/views/auth/login.blade.php
tests/TestCase.php
tests/CreatesApplication.php
tests/Unit/VBulletinModeratorPermissionsTest.php
tests/Unit/ModerationInvariantTest.php
docs/07-vbulletin-moderation-permissions.md
docs/08-دليل-اختبار-الإشراف.md
docs/09-حزمة-رفع-الإشراف-Online.md
```

## إضافات الدفعة الثانية (الحسابات والترقيم والدمج)

### متحكمات جديدة

```text
app/Http/Controllers/RegistrationController.php
app/Http/Controllers/PasswordResetController.php
app/Http/Controllers/AccountController.php
app/Filament/Pages/RegistrationSettings.php
app/Filament/Resources/UserResource/Pages/CreateUser.php
```

### ملفات معدلة إضافية

```text
app/Http/Controllers/Api/ThreadActionController.php
app/Http/Controllers/ForumController.php
app/Http/Controllers/ThreadController.php
app/Filament/Resources/UserResource.php
app/Filament/Resources/UserResource/Pages/EditUser.php
app/Filament/Resources/UserResource/Pages/ListUsers.php
app/Filament/Resources/PendingThreadResource.php
app/Filament/Resources/DeletedThreadResource.php
app/Filament/Resources/DeletedPostResource.php
app/Filament/Resources/ModerationLogResource.php
routes/web.php
```

### قوالب جديدة

```text
resources/views/auth/register.blade.php
resources/views/auth/forgot-password.blade.php
resources/views/auth/reset-password.blade.php
resources/views/account/settings.blade.php
resources/views/vendor/pagination/forum-pages.blade.php
resources/views/filament/pages/registration-settings.blade.php
```

## لا ترفع

```text
.env
vendor/
storage/logs/
قاعدة البيانات أو dumps الإنتاج
أي ملفات clear-cache أو debug مؤقتة
```

## قبل الرفع

1. خذ نسخة كاملة من الملفات الحالية وقاعدة البيانات.
2. تحقق من `moderator`, `moderatorlog`, `deletionlog`, `attachment`, `thread`, `post`, و`forum`.
3. تحقق من أعمدة `moderatorlog` المستخدمة: `userid,forumid,threadid,postid,action,type,threadtitle,ipaddress,id1..id5`.
4. جهز مستخدماً واحداً لكل دور: Administrator 6، Super Moderator 5، Moderator 7، ومستخدم عادي.
5. استخدم قسماً وموضوعات اختبار فقط.

## ترتيب الرفع والتشغيل

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan test --filter=VBulletinModeratorPermissionsTest
php artisan test --filter=ModerationInvariantTest
php artisan test --filter=Moderation
php artisan route:list --path=moderation
```

إذا فشل lint أو route:list أو ظهر خطأ 500، توقف ولا تبدأ عمليات حذف أو دمج.

## الاختبار الإجباري

- الأدمن يرى كل الأقسام ويستطيع الحذف الفعلي بعد التأكيد.
- Super Moderator يحتاج assignment وbitmask، ولا يستطيع الحذف الفعلي.
- Moderator محدود يعمل في قسمه فقط ولا يرى أدوات قسم آخر.
- المستخدم العادي لا يرى أدوات الإشراف ولا يستطيع استدعاء endpoints يدوياً.
- حذف الموضوع يغير `thread.visible` فقط إلى `2` ولا يغير حالات الردود.
- الاستعادة لا تعيد إحياء ردود حُذفت مستقلاً.
- الحذف الفعلي يحذف المرفقات المرتبطة ويسجل audit قبل الإزالة.
- النقل والدمج يعيدان بناء العدادات وتبقى هوية الردود والمرفقات.
- لا تظهر `visible=2` في forum/thread/search/sitemap للعامة.
- لا يوجد `//` داخل JavaScript المضمن.

## rollback

1. أوقف عمليات الإشراف.
2. أعد الملفات من النسخة الاحتياطية.
3. نفذ `php artisan optimize:clear`.
4. لا تستعد قاعدة البيانات تلقائياً؛ راجع `moderatorlog` و`deletionlog` أولاً.
5. إذا وقع تغير بيانات غير مقصود، استعد نسخة قاعدة البيانات المتوافقة مع وقت الاختبار فقط.

لا تتضمن هذه الحزمة migration أو SQL تدميرياً أو اتصالاً بقاعدة الإنتاج.
