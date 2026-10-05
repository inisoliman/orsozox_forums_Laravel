<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModerationLogResource\Pages;
use App\Models\ModeratorLog;
use App\Services\ModerationPermissionService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ModerationLogResource extends Resource
{
    protected static ?string $model = ModeratorLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'سجل الإشراف';
    protected static ?string $modelLabel = 'سجل إشراف';
    protected static ?string $pluralModelLabel = 'سجل الإشراف';
    protected static ?string $navigationGroup = 'الإشراف';

    public static function canCreate(): bool { return false; }
    public static function canViewAny(): bool
    {
        return app(ModerationPermissionService::class)->isModerator(auth()->user());
    }

    public static function table(Table $table): Table
    {
        return $table->paginated([10, 25, 50, 100])->defaultPaginationPageOption(10)->columns([
            Tables\Columns\TextColumn::make('dateline')->label('التاريخ')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::createFromTimestamp($state)->format('Y-m-d H:i') : '-')->sortable(),
            Tables\Columns\TextColumn::make('userid')->label('المشرف')->sortable(),
            Tables\Columns\TextColumn::make('action')->label('العملية')->searchable(),
            Tables\Columns\TextColumn::make('forumid')->label('القسم')->sortable(),
            Tables\Columns\TextColumn::make('threadid')->label('الموضوع')->sortable(),
            Tables\Columns\TextColumn::make('postid')->label('الرد')->sortable(),
        ])->defaultSort('dateline', 'desc');
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return ['index' => Pages\ListModerationLogs::route('/')];
    }
}
