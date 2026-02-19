<?php

use App\Http\Controllers\Api\ThreadApiController;
use App\Http\Controllers\Api\PostApiController;
use App\Http\Controllers\Api\AuthApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات API — محمية بـ Sanctum
|--------------------------------------------------------------------------
*/

// تسجيل الدخول (بدون توثيق)
Route::post('/login', [AuthApiController::class, 'login']);

// مسارات محمية بـ Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // تسجيل الخروج
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // المواضيع
    Route::get('/threads', [ThreadApiController::class, 'index']);
    Route::get('/threads/{id}', [ThreadApiController::class, 'show'])->where('id', '[0-9]+');

    // الردود
    Route::get('/posts/{threadId}', [PostApiController::class, 'index'])->where('threadId', '[0-9]+');
});
