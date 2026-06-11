<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\RedirectorController;
use App\Http\Controllers\OnlineUsersController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\Api\ThreadValidationController;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Route::get('/image-proxy/{hash}', [ImageProxyController::class, 'show'])
    ->where('hash', '[a-f0-9]{64}')
    ->name('image.proxy');

// Email Unsubscribe Route
Route::get('/unsubscribe/{hash}', [UnsubscribeController::class, 'unsubscribe'])
    ->where('hash', '[a-f0-9]{32}') // MD5 Hash length
    ->name('email.unsubscribe');

// Newsletter Subscribe (footer form → saves to email_subscribers table)
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->name('newsletter.subscribe')
    ->middleware(\Illuminate\Routing\Middleware\ThrottleRequests::class . ':5,1');

/*
|--------------------------------------------------------------------------
| المسارات الأمامية — Web Routes
|--------------------------------------------------------------------------
*/

// الصفحة الرئيسية
Route::get('/', [HomeController::class, 'index'])->name('home');

// صفحات E-E-A-T
Route::get('/about', [PageController::class, 'about'])->name('page.about');
Route::get('/editorial-policy', [PageController::class, 'editorial'])->name('page.editorial');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('page.privacy');
Route::get('/contact', [PageController::class, 'contact'])->name('page.contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('page.contact.submit');

// الأقسام
Route::get('/forum/{id}/{slug?}', [ForumController::class, 'show'])->name('forum.show')->where('id', '[0-9]+');

// المواضيع
Route::get('/thread/{id}/{slug?}', [ThreadController::class, 'show'])->name('thread.show')->where('id', '[0-9]+');

// الأعضاء
Route::get('/user/{id}', [UserController::class, 'show'])->name('user.show')->where('id', '[0-9]+');

// البحث
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

// رابط مباشر للمشاركة
Route::get('/posts/{postid}', [PostController::class, 'show'])->name('post.show')->where('postid', '[0-9]+');

// تسجيل الدخول
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/register', [AuthController::class, 'registerClosed'])->name('register');


// خرائط الموقع — Sitemap Index + Sub-sitemaps
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-forums.xml', [SitemapController::class, 'forums'])->name('sitemap.forums');
Route::get('/sitemap-threads-{page}.xml', [SitemapController::class, 'threads'])->name('sitemap.threads')->where('page', '[0-9]+');
Route::get('/sitemap-users-{page}.xml', [SitemapController::class, 'users'])->name('sitemap.users')->where('page', '[0-9]+');

// المتواجدون الآن (تفاصيل للإدارة)
Route::get('/online-users', [OnlineUsersController::class, 'index'])->name('online.users')->middleware('auth');

// عمليات التعديل للواجهة الأمامية (AJAX Moderation)
Route::middleware('auth')->group(function () {
    // فحص تكرار عنوان الموضوع داخل Filament/لوحة الإدارة.
    // يُبقى هنا ضمن web middleware لأن الواجهة ترسل CSRF وتعتمد على جلسة المستخدم.
    Route::post('/api/thread/validate-duplicate', [ThreadValidationController::class, 'checkDuplicate'])
        ->name('thread.validate-duplicate')
        ->middleware(\Illuminate\Routing\Middleware\ThrottleRequests::class . ':20,1');

    // تعديل الموضوع باستخدام CKEditor
    Route::post('/thread/{id}/ajax/edit', [\App\Http\Controllers\Api\ThreadEditController::class, 'update'])->name('thread.ajax.edit');
    // تعديل الردود باستخدام CKEditor
    Route::post('/post/{id}/ajax/edit', [\App\Http\Controllers\Api\PostEditController::class, 'update'])->name('post.ajax.edit');
    // جلب الكود الأصلي (BBCode) من قاعدة البيانات
    Route::get('/post/{id}/ajax/raw', [\App\Http\Controllers\Api\PostEditController::class, 'getRaw'])->name('post.ajax.raw');
    // رفع الصور من داخل المحرر (ملف + رابط)
    Route::post('/editor/upload', [\App\Http\Controllers\ImageUploadController::class, 'upload'])->name('editor.upload');
    Route::post('/editor/upload-url', [\App\Http\Controllers\ImageUploadController::class, 'uploadByUrl'])->name('editor.upload.url');

    // أدوات المشرف الأخرى
    Route::post('/thread/{id}/ajax/move', [\App\Http\Controllers\Api\ThreadActionController::class, 'move'])->name('thread.ajax.move');
    Route::post('/thread/{id}/ajax/delete', [\App\Http\Controllers\Api\ThreadActionController::class, 'destroy'])->name('thread.ajax.delete');
});

// تحويل الروابط الخارجية (Redirector)
Route::get('/redirector.php', [RedirectorController::class, 'index'])->name('redirector');

/*
|--------------------------------------------------------------------------
| تحويلات الروابط القديمة — Legacy vBulletin 301 Redirects
|--------------------------------------------------------------------------
*/
// vBulletin 3 Standard Query Redirects
Route::get('/showthread.php', [RedirectController::class, 'showThread']);
Route::get('/forumdisplay.php', [RedirectController::class, 'forumDisplay']);
Route::get('/member.php', [RedirectController::class, 'member']);

// Legacy Archive Redirects (SEO: 301 preserves link equity)
// /archive/index.php?t-123.html or ?t-123-p-2.html → /thread/123/slug
// مهم للسيو: يلتقط روابط أرشيف vBulletin القديمة التي تأتي كـ query string.
Route::get('/archive/index.php', [RedirectController::class, 'archiveIndex']);

// /archive/index.php/t-123.html → /thread/123/slug
Route::get('/archive/index.php/t-{id}.html', [RedirectController::class, 'archiveThread'])
    ->where('id', '[0-9]+');

// /archive/index.php/f-45.html → /forum/45/slug
Route::get('/archive/index.php/f-{id}.html', [RedirectController::class, 'archiveForum'])
    ->where('id', '[0-9]+');

// Legacy Tag Redirects
// /tags.php?tag=keyword → /search?q=keyword
Route::get('/tags.php', [RedirectController::class, 'tags']);

// vBSEO / Simple Rewriting Redirects
// ملاحظة: حُوِّلت من closures إلى دوال Controller حتى يبقى route:cache آمناً
// (الـ closures في الراوتس تكسر route:cache وتسبب خطأ 405).
Route::get('/f{forum_id}', [RedirectController::class, 'vbseoForum'])->where('forum_id', '[0-9]+');
Route::get('/f{forum_id}/', [RedirectController::class, 'vbseoForum'])->where('forum_id', '[0-9]+');
Route::get('/f{forum_id}/t{thread_id}', [RedirectController::class, 'vbseoThread'])->where(['forum_id' => '[0-9]+', 'thread_id' => '[0-9]+']);
Route::get('/f{forum_id}/t{thread_id}/', [RedirectController::class, 'vbseoThread'])->where(['forum_id' => '[0-9]+', 'thread_id' => '[0-9]+']);

