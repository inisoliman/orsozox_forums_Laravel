<?php

namespace App\Filament\Resources\ThreadResource\Pages;

use App\Filament\Resources\ThreadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditThread extends EditRecord
{
    protected static string $resource = ThreadResource::class;
    protected static ?string $title = 'تعديل الموضوع';

    public ?string $pagetext = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var \App\Models\Thread $thread */
        $thread = $this->record;

        if ($thread->firstPost) {
            $text = $thread->firstPost->pagetext;
            if (str_starts_with($text, '<!-- HTML -->')) {
                $data['pagetext'] = str_replace('<!-- HTML -->', '', $text);
            } else {
                $data['pagetext'] = \App\Helpers\BBCodeParser::parse($text);
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pagetext = $data['pagetext'] ?? '';
        unset($data['pagetext']);
        return $data;
    }

    protected function afterSave(): void
    {
        /** @var \App\Models\Thread $thread */
        $thread = $this->record;
        if ($thread->firstPost) {
            $thread->firstPost->update([
                'pagetext' => '<!-- HTML -->' . $this->pagetext
            ]);
        }
    }
}
