<?php
namespace App\Filament\Resources\ForumResource\Pages;
use App\Filament\Resources\ForumResource;
use Filament\Resources\Pages\ListRecords;

class ListForums extends ListRecords
{
    protected static string $resource = ForumResource::class;
    protected static ?string $title = 'الأقسام';
}
