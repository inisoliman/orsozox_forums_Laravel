<?php

namespace App\Filament\Resources\NewsTickerResource\Pages;

use App\Filament\Resources\NewsTickerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageNewsTickers extends ManageRecords
{
    protected static string $resource = NewsTickerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة خبر جديد'),
        ];
    }
}
