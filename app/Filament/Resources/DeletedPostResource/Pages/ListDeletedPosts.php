<?php

namespace App\Filament\Resources\DeletedPostResource\Pages;

use App\Filament\Resources\DeletedPostResource;
use Filament\Resources\Pages\ListRecords;

class ListDeletedPosts extends ListRecords
{
    protected static string $resource = DeletedPostResource::class;
}
