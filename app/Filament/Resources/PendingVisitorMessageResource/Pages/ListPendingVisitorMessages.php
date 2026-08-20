<?php

namespace App\Filament\Resources\PendingVisitorMessageResource\Pages;

use App\Filament\Resources\PendingVisitorMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListPendingVisitorMessages extends ListRecords
{
    protected static string $resource = PendingVisitorMessageResource::class;
    protected static ?string $title = 'رسائل زوار قيد المراجعة';
}