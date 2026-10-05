<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeletedPostResource\Pages;
use App\Models\Post;
use App\Services\ModerationActionService;
use App\Services\ModerationPermissionService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeletedPostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static ?string $navigationLabel = 'الردود المحذوفة';
    protected static ?string $modelLabel = 'رد محذوف';
    protected static ?string $pluralModelLabel = 'الردود المحذوفة';
    protected static ?string $navigationGroup = 'الإشراف';

    public static function canCreate(): bool { return false; }

    public static function canViewAny(): bool
    {
        return app(ModerationPermissionService::class)->isModerator(auth()->user());
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->where('visible', 2)->whereHas('thread');
        $permissions = app(ModerationPermissionService::class);
        $user = auth()->user();
        if ($permissions->isAdministrator($user)) {
            return $query;
        }
        if (! $user || ! $permissions->isModerator($user)) {
            return $query->whereRaw('1 = 0');
        }
        $assignments = $user->moderatorAssignments();
        if ((clone $assignments)->where('forumid', \App\Support\VBulletinModeratorPermissions::GLOBAL_FORUM_ID)->exists()) {
            return $query;
        }
        return $query->whereHas('thread', function ($threadQuery) use ($assignments) {
            $threadQuery->whereIn('forumid', (clone $assignments)->select('forumid'));
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->columns([
            Tables\Columns\TextColumn::make('postid')->label('ID')->sortable(),
            Tables\Columns\TextColumn::make('thread.title')->label('الموضوع')->limit(50),
            Tables\Columns\TextColumn::make('username')->label('الكاتب'),
            Tables\Columns\TextColumn::make('dateline')->label('التاريخ')->sortable()
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::createFromTimestamp($state)->diffForHumans() : '-'),
        ])->defaultSort('dateline', 'desc')->actions([
            Tables\Actions\Action::make('restore')
                ->label('استعادة')
                ->requiresConfirmation()
                ->action(function (Post $record) {
                    app(ModerationActionService::class)->restorePost($record, auth()->user());
                    Notification::make()->title('تمت استعادة الرد')->success()->send();
                }),
            Tables\Actions\Action::make('hard_delete')
                ->label('حذف فعلي')
                ->color('danger')
                ->visible(fn () => app(ModerationPermissionService::class)->isAdministrator(auth()->user()))
                ->requiresConfirmation()
                ->action(function (Post $record) {
                    app(ModerationActionService::class)->hardDeletePost($record, auth()->user());
                    Notification::make()->title('تم الحذف النهائي')->success()->send();
                }),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDeletedPosts::route('/')];
    }
}
