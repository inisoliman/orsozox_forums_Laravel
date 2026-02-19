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
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| المسارات الأمامية — Web Routes
|--------------------------------------------------------------------------
*/

// الصفحة الرئيسية
Route::get('/', [HomeController::class, 'index'])->name('home');

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

// خرائط الموقع — Sitemap Index + Sub-sitemaps
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-forums.xml', [SitemapController::class, 'forums'])->name('sitemap.forums');
Route::get('/sitemap-threads-{page}.xml', [SitemapController::class, 'threads'])->name('sitemap.threads')->where('page', '[0-9]+');

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
