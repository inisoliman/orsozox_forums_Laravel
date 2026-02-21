<?php

namespace App\Services\LocalAI;

class ContentCleanerService
{
    /**
     * ينظف النص من الـ HTML و BBCode والضوضاء للحصول على نص نقي للمقارنة أو الذكاء الاصطناعي.
     */
    public function cleanHtml(string $html): string
    {
        // 1. إزالة الـ HTML
        $text = strip_tags($html);

        // 2. إزالة BBCode (مثل [b], [quote=user])
        $text = preg_replace('/\[\/?\w+(?:=[^\]]+)?\]/i', '', $text);

        // 3. إزالة الروابط والإيميلات
        $text = preg_replace('/https?:\/\/\S+/i', '', $text);
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i', '', $text);

        return $text;
    }

    /**
     * تطبيع النص العربي للذكاء الاصطناعي (توحيد الهمزات، التاء المربوطة، إزالة التشكيل)
     */
    public function normalizeArabic(string $text): string
    {
        // 1. إزالة التشكيل العربي
        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06DC}\x{06DF}-\x{06E8}\x{06EA}-\x{06ED}]/u', '', $text);

        // 2. تطبيع الألف (أ إ آ -> ا)
        $text = preg_replace('/[إأآا]/u', 'ا', $text);

        // 3. تطبيع الياء (ي ى ئ -> ي)
        $text = preg_replace('/[يىئ]/u', 'ي', $text);

        // 4. تطبيع التاء المربوطة والهاء (ة ه -> ه)
        $text = preg_replace('/[ةه]/u', 'ه', $text);

        // 5. إزالة التطويل (ـ)
        $text = preg_replace('/ـ+/u', '', $text);

        // 6. إزالة الرموز والأرقام الأجنبية والإبقاء على الحروف العربية فقط (اختياري، للكلمات المفتاحية)
        $text = preg_replace('/[^\p{Arabic}\s]/u', ' ', $text);

        // 7. إزالة المسافات المتعددة المتتالية
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }
}
