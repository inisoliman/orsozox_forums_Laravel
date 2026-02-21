<?php

namespace App\Services\LocalAI;

class KeywordExtractorService
{
    private ContentCleanerService $cleaner;

    // قائمة بأكثر الكلمات العربية والإنجليزية استخداماً كلوصول للحروف (Stop words)
    private array $stopWords = [
        'في',
        'من',
        'على',
        'إلى',
        'عن',
        'مع',
        'هل',
        'كيف',
        'متى',
        'لماذا',
        'أين',
        'ما',
        'هذا',
        'هذه',
        'التي',
        'الذي',
        'و',
        'أو',
        'ثم',
        'يا',
        'لا',
        'لم',
        'لن',
        'بين',
        'ان',
        'انما',
        'ايا',
        'اذا',
        'انه',
        'انها',
        'انت',
        'انا',
        'نحن',
        'هم',
        'هن',
        'هما',
        'هو',
        'هي',
        'كل',
        'بعض',
        'غير',
        'سوى',
        'حتى',
        'الا',
        'لكن',
        'ليت',
        'لعل',
        'كان',
        'صار',
        'اصبح',
        'امسى',
        'ظل',
        'بات',
        'مازال',
        'مابرح',
        'ماانفك',
        'مادام', // أفعال ناسخة
        'قد',
        'لقد',
        'سوف',
        'بئس',
        'نعم',
        'حبذا',
        'لااسيم',
        'الذين',
        'اللتان',
        'اللذان',
        'التالي',
        'السابق',
        'اي',
        'ايهما',
        'نحو',
        'عند',
        'بعد',
        'قبل',
        'دون',
        'تحت',
        'فوق',
        'خلف',
        'امام',
        'يمين',
        'يسار',
        'واحد',
        'اثنان',
        'ثلاثة',
        'ايضا',
        'كثيرا',
        'قليلا',
        'جدا',
        'ربما',
        'فقط',
        'كذلك',
        'قال',
        'يقول',
        'قالت',
        'قلنا',
        'كانت',
        'يكون',
        'هناك',
        'هنا',
        'والتي',
        'والذي',
        'لعل',
        'عبر',
        'حول',
        'بسبب',
        'رغم',
        'اثناء',
        'ضمن',
        'بواسطة',
        'the',
        'and',
        'or',
        'in',
        'on',
        'at',
        'to',
        'for',
        'with',
        'by',
        'of',
        'from',
        'about',
        'as',
        'into',
        'like',
        'through',
        'after',
        'over',
        'between',
        'out',
        'against',
        'during',
        'without',
        'before',
        'under',
        'around',
        'among'
    ];

    public function __construct(ContentCleanerService $cleaner)
    {
        $this->cleaner = $cleaner;
    }

    /**
     * يستخرج أهم الكلمات المفتاحية من نص معين بترتيب الأكثر تكراراً وأهمية.
     */
    public function extract(string $text, int $limit = 7): array
    {
        // 1. تنظيف النص بالكامل من خلال الـ Cleaner Service
        $cleanedText = $this->cleaner->cleanHtml($text);

        // 2. تطبيع النص العربي فقط (إزالة التشكيل، التطويل، الأرقام) لتحليل أدق للكلمات
        $normalizedText = $this->cleaner->normalizeArabic($cleanedText);

        // 3. تقسيم النص إلى كلمات
        $words = explode(' ', mb_strtolower($normalizedText, 'UTF-8'));

        // 4. فلترة الكلمات وحساب التكرار
        $wordCounts = [];
        foreach ($words as $word) {
            $word = trim($word);

            // استبعاد الكلمات الفارغة، القصيرة جداً، أو التي من ضمن الـ Stop Words
            if (mb_strlen($word) > 2 && !in_array($word, $this->stopWords)) {
                if (isset($wordCounts[$word])) {
                    $wordCounts[$word]++;
                } else {
                    $wordCounts[$word] = 1;
                }
            }
        }

        // 5. ترتيب الكلمات تنازلياً حسب التكرار
        arsort($wordCounts);

        // 6. استخراج أقوى الكلمات (المفاتيح) بالحد المطلوب
        $topKeywords = array_slice(array_keys($wordCounts), 0, $limit);

        return $topKeywords;
    }

    /**
     * إرجاع الكلمات المفتاحية كسلسلة نصية مفصولة بفواصل (لاستخدامها في الميتا تاج)
     */
    public function extractAsString(string $text, int $limit = 7, string $separator = ', '): string
    {
        $keywords = $this->extract($text, $limit);
        return implode($separator, $keywords);
    }
}
