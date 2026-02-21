<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Thread;
use Illuminate\Support\Str;

class ThreadValidationController extends Controller
{
    /**
     * Check if a thread title already exists or is highly similar.
     * Expected POST: { "title": "...", "forum_id": 123 }
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:5|max:150',
            'forum_id' => 'required|integer',
        ]);

        $title = mb_strtolower(trim($request->input('title')), 'UTF-8');
        $forumId = (int) $request->input('forum_id');

        // تنظيف مبدئي لتطبيع النص قبل المقارنة
        $cleanedTitle = $this->normalizeArabicText($title);

        if (mb_strlen($cleanedTitle) < 5) {
            return response()->json(['status' => 'too_short', 'similarity' => 0]);
        }

        // سحب آخر 1000 موضوع من نفس القسم للمقارنة في ذاكرة الـ RAM للحفاظ على سرعة الاستجابة
        $threads = Thread::where('forumid', $forumId)
            ->orderBy('threadid', 'desc')
            ->limit(1000)
            ->select('threadid', 'title', 'slug')
            ->get();

        $highestSimilarity = 0;
        $matchedThreads = [];

        foreach ($threads as $thread) {
            $dbTitle = $this->normalizeArabicText(mb_strtolower(trim($thread->title), 'UTF-8'));

            // 1. فحص التطابق التام
            if ($cleanedTitle === $dbTitle) {
                return response()->json([
                    'status' => 'exact_match',
                    'similarity' => 100,
                    'matches' => [
                        [
                            'id' => $thread->threadid,
                            'title' => $thread->title,
                            'url' => route('thread.show', ['id' => $thread->threadid, 'slug' => $thread->slug])
                        ]
                    ]
                ]);
            }

            // 2. استخدام Similar Text للغة العربية
            $percentage = 0;
            similar_text($cleanedTitle, $dbTitle, $percentage);

            if ($percentage > $highestSimilarity) {
                $highestSimilarity = $percentage;
            }

            if ($percentage > 70) {
                $matchedThreads[] = [
                    'id' => $thread->threadid,
                    'title' => $thread->title,
                    'similarity' => round($percentage, 1),
                    'url' => route('thread.show', ['id' => $thread->threadid, 'slug' => $thread->slug ?? Str::slug($thread->title)])
                ];
            }
        }

        // تحديد الحالة بناءً على النسبة
        $status = 'safe';
        if ($highestSimilarity >= 90) {
            $status = 'blocked'; // التكرار شبه مؤكد
        } elseif ($highestSimilarity >= 70) {
            $status = 'warning'; // تشابه ملحوظ
        }

        // ترتيب أفضل التطابقات
        usort($matchedThreads, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // إرجاع أعلى 5 نتائج فقط
        $matchedThreads = array_slice($matchedThreads, 0, 5);

        return response()->json([
            'status' => $status,
            'similarity' => round($highestSimilarity, 1),
            'matches' => $matchedThreads
        ]);
    }

    /**
     * تطبيع النص العربي لإزالة همزات التشكيل وغيرها لتكون المقارنة عادلة.
     */
    private function normalizeArabicText(string $text): string
    {
        // إزالة التشكيل
        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06DC}\x{06DF}-\x{06E8}\x{06EA}-\x{06ED}]/u', '', $text);

        // تطبيع الألف
        $text = preg_replace('/[إأآا]/u', 'ا', $text);

        // تطبيع الياء
        $text = preg_replace('/[يىئ]/u', 'ي', $text);

        // تطبيع التاء المربوطة والهاء
        $text = preg_replace('/[ةه]/u', 'ه', $text);

        // إزالة المسافات المتكررة والرموز
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }
}
