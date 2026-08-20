<?php

namespace App\Filament\Resources\PendingPostResource\Pages;

use App\Filament\Resources\PendingPostResource;
use Filament\Resources\Pages\ListRecords;

class ListPendingPosts extends ListRecords
{
    protected static string $resource = PendingPostResource::class;
    protected static ?string $title = 'ردود قيد المراجعة';
}