<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageUploadRequest;
use App\Services\WatermarkService;
use App\Services\SettingsService;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class ImageUploadController extends Controller
{
    /**
     * Handle image upload from CKEditor or any AJAX uploader.
     * Pipeline: Validate → Resize → Watermark → WebP → Save → Return URL
     */
    public function upload(ImageUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('upload');

            // Security: validate MIME server-side (double check)
            $realMime = $file->getMimeType();
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($realMime, $allowedMimes)) {
                return response()->json([
                    'uploaded' => false,
                    'error' => ['message' => 'نوع الملف غير مسموح.'],
                ], 400);
            }

            // Security: prevent double extension attack
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
            if (str_contains($nameWithoutExt, '.')) {
                return response()->json([
                    'uploaded' => false,
                    'error' => ['message' => 'اسم ملف غير صالح.'],
                ], 400);
            }

            // Initialize Intervention Image with GD driver
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            // Step 1: Resize if width > 1600px (maintain aspect ratio)
            if ($image->width() > 1600) {
                $image->scale(width: 1600);
            }

            // Step 2: Apply watermark
            $settingsService = app(SettingsService::class);
            $watermarkService = new WatermarkService($settingsService);
            $image = $watermarkService->apply($image);

            // Step 3: Convert to WebP (quality 85)
            $encoded = $image->toWebp(85);

            // Step 4: Generate save path — storage/app/public/uploads/posts/YYYY/MM/
            $year = date('Y');
            $month = date('m');
            $directory = "uploads/posts/{$year}/{$month}";

            // Ensure directory exists
            Storage::disk('public')->makeDirectory($directory);

            // Generate unique filename
            $filename = uniqid('img_') . '.webp';
            $fullPath = "{$directory}/{$filename}";

            // Save to storage
            Storage::disk('public')->put($fullPath, (string) $encoded);

            // Generate public URL
            $publicUrl = asset('storage/' . $fullPath);

            return response()->json([
                'uploaded' => true,
                'url' => $publicUrl,
                // CKEditor 5 format
                'default' => $publicUrl,
            ]);

        } catch (\Throwable $e) {
            Log::error('Image upload failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'uploaded' => false,
                'error' => ['message' => 'فشل في رفع الصورة. يرجى المحاولة مرة أخرى.'],
            ], 500);
        }
    }

    /**
     * Handle image insertion via URL (download, process, re-host).
     * This supports the "Insert Image via URL" feature in CKEditor.
     */
    public function uploadByUrl(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $url = $request->input('url');

            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                return response()->json([
                    'uploaded' => false,
                    'error' => ['message' => 'رابط غير صالح.'],
                ], 400);
            }

            // Download the image with timeout
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $imageData = @file_get_contents($url, false, $context);
            if ($imageData === false) {
                return response()->json([
                    'uploaded' => false,
                    'error' => ['message' => 'فشل في تحميل الصورة من الرابط.'],
                ], 400);
            }

            // Check file size (max 5MB)
            if (strlen($imageData) > 5 * 1024 * 1024) {
                return response()->json([
                    'uploaded' => false,
                    'error' => ['message' => 'الصورة أكبر من 5 ميجابايت.'],
                ], 400);
            }

            // Validate MIME type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($imageData);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($mime, $allowedMimes)) {
                return response()->json([
                    'uploaded' => false,
                    'error' => ['message' => 'نوع الصورة غير مسموح.'],
                ], 400);
            }

            // Process with Intervention Image
            $manager = new ImageManager(new Driver());
            $image = $manager->read($imageData);

            // Resize if too large
            if ($image->width() > 1600) {
                $image->scale(width: 1600);
            }

            // Apply watermark
            $settingsService = app(SettingsService::class);
            $watermarkService = new WatermarkService($settingsService);
            $image = $watermarkService->apply($image);

            // Convert to WebP
            $encoded = $image->toWebp(85);

            // Save
            $year = date('Y');
            $month = date('m');
            $directory = "uploads/posts/{$year}/{$month}";
            Storage::disk('public')->makeDirectory($directory);

            $filename = uniqid('img_') . '.webp';
            $fullPath = "{$directory}/{$filename}";
            Storage::disk('public')->put($fullPath, (string) $encoded);

            $publicUrl = asset('storage/' . $fullPath);

            return response()->json([
                'uploaded' => true,
                'url' => $publicUrl,
                'default' => $publicUrl,
            ]);

        } catch (\Throwable $e) {
            Log::error('Image URL upload failed: ' . $e->getMessage());

            return response()->json([
                'uploaded' => false,
                'error' => ['message' => 'فشل في معالجة الصورة.'],
            ], 500);
        }
    }
}
