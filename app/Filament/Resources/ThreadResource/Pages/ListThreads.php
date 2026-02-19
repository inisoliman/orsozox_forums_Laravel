<?php

namespace App\Filament\Resources\ThreadResource\Pages;

use App\Filament\Resources\ThreadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListThreads extends ListRecords
{
    protected static string $resource = ThreadResource::class;
    protected static ?string $title = 'المواضيع';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة موضوع'),
        ];
    }
}
