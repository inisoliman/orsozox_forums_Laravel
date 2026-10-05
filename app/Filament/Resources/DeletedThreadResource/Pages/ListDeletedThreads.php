<?php

namespace App\Filament\Resources\DeletedThreadResource\Pages;

use App\Filament\Resources\DeletedThreadResource;
use Filament\Resources\Pages\ListRecords;

class ListDeletedThreads extends ListRecords
{
    protected static string $resource = DeletedThreadResource::class;
}
