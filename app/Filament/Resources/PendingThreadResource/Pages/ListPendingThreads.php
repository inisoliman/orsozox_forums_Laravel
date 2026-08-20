<?php

namespace App\Filament\Resources\PendingThreadResource\Pages;

use App\Filament\Resources\PendingThreadResource;
use Filament\Resources\Pages\ListRecords;

class ListPendingThreads extends ListRecords
{
    protected static string $resource = PendingThreadResource::class;
    protected static ?string $title = 'مواضيع قيد المراجعة';
}