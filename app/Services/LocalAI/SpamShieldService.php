<?php

namespace App\Services\LocalAI;

class SpamShieldService
{
    private ContentCleanerService $cleaner;

    // كلمات شائعة في المنتديات الإعلانية المزعجة (Spam)
    private array $spamBlacklist = [
        'viagra',
        'casino',
        'betting',
        'porno',
        'xxx',
        'seo services',
        'buy followers',
        'للبيع',
        'أرخص الأسعار',
        'خصم حصري',
        'اشتر الآن',
        'قروض',
        'تسديد ديون',
        'جلب الحبيب',
        'سحر',
        'شعوذة',
        'فك السحر',
        'طبيب روحاني'
    ];

    public function __construct(ContentCleanerService $cleaner)
    {
        $this->cleaner = $cleaner;
    }

    /**
     * تقييم نسبة احتمالية كون النص مزعج (Spam Score)
     * من 0 (نظيف) إلى 100 (سبام مؤكد)
     */
    public function calculateSpamScore(string $title, string $content): int
    {
        $score = 0;

        // 1. فحص وجود روابط في العنوان مباشرة (غالباً سبام 100%)
        if (preg_match('/https?:\/\//i', $title)) {
            $score += 60;
        }

        // 2. تحليل نسبة الروابط في المحتوى مقارنة بطوله (Link Density)
        $contentLength = mb_strlen(strip_tags($content));
        preg_match_all('/https?:\/\/\([^"\' <\n\r]+\)/i', $content, $matches);
        $linksCount = count($matches[0]);

        if ($linksCount > 0) {
            if ($contentLength < 100 && $linksCount >= 2) {
                // نص قصير جداً به أكثر من رابط
                $score += 40;
            } elseif ($linksCount > 5) {
                // روابط مبالغ فيها
                $score += 30;
            }
        }

        // 3. تحليل الكلمات المحظورة (Blacklist)
        $normalizedTitle = mb_strtolower($title, 'UTF-8');
        $normalizedContent = mb_strtolower(strip_tags($content), 'UTF-8');

        foreach ($this->spamBlacklist as $spamWord) {
            if (str_contains($normalizedTitle, $spamWord)) {
                $score += 50; // كلمة سبام في العنوان خطيرة جداً
            }
            if (str_contains($normalizedContent, $spamWord)) {
                $score += 25;
            }
        }

        // 4. تحليل الحروف الإنجليزية الكبيرة (Uppercase Ratio)
        // السبام الأجنبي يستخدم الـ CAPS بكثرة لجذب الانتباه
        $englishText = preg_replace('/[^a-zA-Z]/', '', $content);
        $englishLength = strlen($englishText);
        if ($englishLength > 50) {
            $uppercaseCount = strlen(preg_replace('/[^A-Z]/', '', $englishText));
            $uppercaseRatio = $uppercaseCount / $englishLength;

            if ($uppercaseRatio > 0.6) {
                $score += 30; // أكثر من 60% من الحروف الإنجليزية كبيرة
            }
        }

        // 5. تكرار نفس الكلمة بشكل غير طبيعي (Keyword Stuffing)
        // ... (يمكن بناء خوارزمية هنا لعد تكرار الكلمات المتجاورة لاحقاً)

        return min($score, 100); // أقصى حد 100
    }

    /**
     * هل النص يعتبر سبام؟
     */
    public function isSpam(string $title, string $content, int $threshold = 80): bool
    {
        return $this->calculateSpamScore($title, $content) >= $threshold;
    }
}
