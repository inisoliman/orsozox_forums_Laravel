<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class WebpHelper
{
    /**
     * يحول مسار الصورة الأصلية إلى مسار WebP ديناميكياً
     * وإذا لم تكن موجودة يقوم بإنشائها عبر مكتبة GD المدمجة
     * On-the-fly Image Conversion
     *
     * @param string $sourcePath المسار المطلق للصورة (داخل public) أو رابط محلي
     * @param int $quality جودة الضغط الافتراضية 80
     * @return string يعيد المسار العام للصورة (Asset URL)
     */
    public static function convertAndGet(string $sourcePath, int $quality = 80): string
    {
        // 1. تنظيف المسار واستخراج المسار الداخلي من الـ URL إذا تم تمريره كـ Asset
        $appUrl = config('app.url');
        if (str_starts_with($sourcePath, $appUrl)) {
            $sourcePath = str_replace($appUrl . '/', '', $sourcePath);
        }

        // إزالة / الشرطة الأولى إن وجدت لتطابق مسارات public_path
        $sourcePath = ltrim($sourcePath, '/');

        $absoluteSource = public_path($sourcePath);

        // 2. التحقق من وجود الصورة الأصلية، وإلّا أعدّ نفس المسار لحماية النظام من التوقف
        if (!File::exists($absoluteSource)) {
            return asset($sourcePath);
        }

        // لا داعي لتحويل صور متحركة أو SVG
        $extension = strtolower(File::extension($absoluteSource));
        if (in_array($extension, ['gif', 'svg', 'webp'])) {
            return asset($sourcePath);
        }

        // 3. تجهيز مسار واسم صورة الـ WebP الحديثة
        $webpPath = preg_replace('/\.' . $extension . '$/i', '.webp', $sourcePath);
        $absoluteWebp = public_path($webpPath);

        // 4. إذا كانت نسخة WebP موجودة مسبقاً، لا تُعد إنشاءها
        if (File::exists($absoluteWebp)) {
            return asset($webpPath);
        }

        // 5. عملية الإنشاء والصناعة باستخدام مكتبة GD
        try {
            $image = null;

            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    if (function_exists('imagecreatefromjpeg')) {
                        $image = @imagecreatefromjpeg($absoluteSource);
                    }
                    break;
                case 'png':
                    if (function_exists('imagecreatefrompng')) {
                        $image = @imagecreatefrompng($absoluteSource);
                        if ($image) {
                            // الحفاظ على شفافية الـ PNG
                            imagepalettetotruecolor($image);
                            imagealphablending($image, true);
                            imagesavealpha($image, true);
                        }
                    }
                    break;
            }

            if ($image) {
                // إنشاء ملف الـ WebP وحفظه ليوفر استهلاك الـ CPU في المرات القادمة
                if (function_exists('imagewebp')) {
                    imagewebp($image, $absoluteWebp, $quality);
                    imagedestroy($image);
                    return asset($webpPath);
                }
            }
        } catch (\Exception $e) {
            Log::error('WebP Conversion Failed: ' . $e->getMessage(), ['file' => $absoluteSource]);
        }

        // 6. العودة للصورة الأصلية بأمان (Fallback) في حالة فشل التحويل لأي سبب (مثل نقص مكتبة GD)
        return asset($sourcePath);
    }
}
