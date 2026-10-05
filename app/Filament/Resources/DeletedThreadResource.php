<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeletedThreadResource\Pages;
use App\Models\Thread;
use App\Services\ModerationActionService;
use App\Services\ModerationPermissionService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeletedThreadResource extends Resource
{
    protected static ?string $model = Thread::class;
    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static ?string $navigationLabel = 'المواضيع المحذوفة';
    protected static ?string $modelLabel = 'موضوع محذوف';
    protected static ?string $pluralModelLabel = 'المواضيع المحذوفة';
    protected static ?string $navigationGroup = 'الإشراف';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return app(ModerationPermissionService::class)
            ->scopeToPermittedForums(parent::getEloquentQuery()->where('visible', 2), auth()->user());
    }

    public static function canViewAny(): bool
    {
        return app(ModerationPermissionService::class)->isModerator(auth()->user());
    }

    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('threadid')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('forum.title')->label('القسم')->badge(),
                Tables\Columns\TextColumn::make('postusername')->label('الكاتب')->searchable(),
                Tables\Columns\TextColumn::make('dateline')->label('التاريخ')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::createFromTimestamp($state)->diffForHumans() : '-'),
            ])
            ->defaultSort('dateline', 'desc')
            ->actions([
                Tables\Actions\Action::make('restore')
                    ->label('استعادة')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Thread $record) {
                        app(ModerationActionService::class)->restoreThread($record, auth()->user());
                        Notification::make()->title('تمت استعادة الموضوع')->success()->send();
                    }),
                Tables\Actions\Action::make('hard_delete')
                    ->label('حذف فعلي')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn () => app(\App\Services\ModerationPermissionService::class)->isAdministrator(auth()->user()))
                    ->requiresConfirmation()
                    ->action(function (Thread $record) {
                        app(ModerationActionService::class)->hardDeleteThread($record, auth()->user());
                        Notification::make()->title('تم الحذف النهائي')->success()->send();
                    }),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDeletedThreads::route('/')];
    }
}
