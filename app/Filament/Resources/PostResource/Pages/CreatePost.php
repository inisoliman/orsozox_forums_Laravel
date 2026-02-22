<?php
namespace App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
    protected static ?string $title = 'إضافة رد جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $cleaner = new \App\Services\LocalAI\ContentCleanerService();
        $spamShield = new \App\Services\LocalAI\SpamShieldService($cleaner);

        $threadTitle = '';
        if (isset($data['threadid'])) {
            $thread = \App\Models\Thread::find($data['threadid']);
            $threadTitle = $thread ? $thread->title : '';
        }

        $spamScore = $spamShield->calculateSpamScore($threadTitle, $data['pagetext'] ?? '');

        if ($spamScore > 80) {
            $data['visible'] = 0; // مخفي تلقائيا للمراجعة
        }

        // Set post author
        if (empty($data['username'])) {
            $data['userid'] = auth()->id() ?? 1;
            $data['username'] = auth()->user()?->username ?? 'Admin';
        } else {
            // Admin set the author manually
            $data['userid'] = \App\Models\User::where('username', $data['username'])->value('userid') ?? 1;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $post = $this->record;

        // استخراج الكلمات المفتاحية الذكية من الرد
        $cleaner = new \App\Services\LocalAI\ContentCleanerService();
        $extractor = new \App\Services\LocalAI\KeywordExtractorService($cleaner);

        $keywords = $extractor->extract($post->pagetext ?? '', 5);

        if (!empty($keywords)) {
            // إضافة الكلمات لجدول thread_keywords إذا كان موجوداً
            if (\Illuminate\Support\Facades\Schema::hasTable('thread_keywords')) {
                $insertData = [];
                foreach ($keywords as $kw) {
                    $exists = \Illuminate\Support\Facades\DB::table('thread_keywords')
                        ->where('threadid', $post->threadid)
                        ->where('keyword', $kw)
                        ->exists();

                    if (!$exists) {
                        $insertData[] = [
                            'threadid' => $post->threadid,
                            'keyword' => $kw,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                if (!empty($insertData)) {
                    \Illuminate\Support\Facades\DB::table('thread_keywords')->insert($insertData);
                }
            }
        }
    }
}
