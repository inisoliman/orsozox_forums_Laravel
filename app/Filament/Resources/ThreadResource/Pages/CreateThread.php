<?php

namespace App\Filament\Resources\ThreadResource\Pages;

use App\Filament\Resources\ThreadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateThread extends CreateRecord
{
    protected static string $resource = ThreadResource::class;
    protected static ?string $title = 'إضافة موضوع جديد';

    public ?string $pagetext = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // فحص التسبام عبر الذكاء المحلي (SpamShieldService)
        $cleaner = new \App\Services\LocalAI\ContentCleanerService();
        $spamShield = new \App\Services\LocalAI\SpamShieldService($cleaner);

        $spamScore = $spamShield->calculateSpamScore($data['title'], $data['pagetext'] ?? '');

        if ($spamScore > 80) {
            $data['visible'] = 0; // مخفي تلقائيا للمراجعة
        }

        $this->pagetext = $data['pagetext'] ?? '';
        unset($data['pagetext']);

        // Set thread author based on currently authenticated admin
        if (empty($data['postusername'])) {
            $data['postuserid'] = auth()->id() ?? 1;
            $data['postusername'] = auth()->user()?->username ?? 'Admin';
        } else {
            // Admin set the author manually
            $data['postuserid'] = \App\Models\User::where('username', $data['postusername'])->value('userid') ?? 1;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var \App\Models\Thread $thread */
        $thread = $this->record;

        $post = new \App\Models\Post();
        $post->threadid = $thread->threadid;
        $post->userid = $thread->postuserid;
        $post->username = $thread->postusername;
        $post->pagetext = '<!-- HTML -->' . $this->pagetext;
        $post->dateline = time();
        $post->visible = $thread->visible;
        $post->title = $thread->title;
        $post->save();

        // Update the thread with the created post IDs
        $thread->firstpostid = $post->postid;
        $thread->lastpost = time();
        $thread->lastposterid = $post->userid;
        $thread->save();
    }
}
