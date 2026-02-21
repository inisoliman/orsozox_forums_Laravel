<?php

namespace App\Filament\Resources\ThreadResource\Pages;

use App\Filament\Resources\ThreadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateThread extends CreateRecord
{
    protected static string $resource = ThreadResource::class;
    protected static ?string $title = 'إضافة موضوع جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // نستخرج المحتوى إذا أضفنا استدعاء API خارجي، هنا نستخدم العنوان فقط كعينة مبدئية لتبسيط الفلترة
        // فحص التسبام عبر الذكاء المحلي (SpamShieldService)
        $cleaner = new \App\Services\LocalAI\ContentCleanerService();
        $spamShield = new \App\Services\LocalAI\SpamShieldService($cleaner);

        $spamScore = $spamShield->calculateSpamScore($data['title'], $data['title']); // تمرير العنوان كـ content مؤقتاً

        if ($spamScore > 80) {
            $data['visible'] = 0; // مخفي تلقائيا للمراجعة
        }

        return $data;
    }
}
