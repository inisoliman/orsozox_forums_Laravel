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
use App\Http\Controllers\Api\ThreadValidationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Route::get('/run-local-ai-migration', function () {
    if (!Schema::hasTable('thread_keywords')) {
        Schema::create('thread_keywords', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('threadid');
            $table->string('keyword', 100);
            $table->timestamps();

            $table->foreign('threadid')->references('threadid')->on('thread')->onDelete('cascade');
            $table->index('keyword');
        });
        return 'Table thread_keywords created successfully.';
    }
    return 'Table already exists.';
});

Route::get('/debug-admin', function () {
    $routes = collect(Route::getRoutes())->filter(function ($route) {
        return str_contains($route->uri(), 'admin');
    })->map(function ($route) {
        return $route->uri() . ' (' . implode(', ', $route->methods()) . ')';
    })->values();

    return response()->json([
        'admin_routes' => $routes,
        'app_url' => config('app.url'),
        'filament_path' => filament()->getCurrentPanel()?->getPath(),
        'filament_id' => filament()->getCurrentPanel()?->getId()
    ]);
});
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
Route::get('/search/results', [SearchController::class, 'results'])->name('search.results');

// تسجيل الدخول
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/register', function () {
    return redirect()->route('login')->with('error', 'التسجيل مغلق حالياً');
})->name('register');

// خرائط الموقع — Sitemap Index + Sub-sitemaps
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-forums.xml', [SitemapController::class, 'forums'])->name('sitemap.forums');
Route::get('/sitemap-threads-{page}.xml', [SitemapController::class, 'threads'])->name('sitemap.threads')->where('page', '[0-9]+');
Route::get('/sitemap-users-{page}.xml', [SitemapController::class, 'users'])->name('sitemap.users')->where('page', '[0-9]+');

// المتواجدون الآن (تفاصيل للإدارة)
Route::get('/online-users', [OnlineUsersController::class, 'index'])->name('online.users')->middleware('auth');

// تحويل الروابط الخارجية (Redirector)
Route::get('/redirector.php', [RedirectorController::class, 'index'])->name('redirector');

/*
|--------------------------------------------------------------------------
| تحويلات الروابط القديمة — Legacy vBulletin 301 Redirects
|--------------------------------------------------------------------------
*/
Route::get('/showthread.php', [RedirectController::class, 'showThread']);
Route::get('/forumdisplay.php', [RedirectController::class, 'forumDisplay']);
Route::get('/member.php', [RedirectController::class, 'member']);
