<?php

namespace App\Filament\Resources\EmailSubscriberResource\Pages;

use App\Filament\Resources\EmailSubscriberResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailSubscribers extends ListRecords
{
    protected static string $resource = EmailSubscriberResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة مشترك يدوياً'),
        ];
    }
}
