<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Models\Thread;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\ModerationActionService;
use Filament\Notifications\Notification;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationLabel = 'الردود';
    protected static ?string $modelLabel = 'رد';
    protected static ?string $pluralModelLabel = 'الردود';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereNotIn('postid', function ($query) {
            $query->select('firstpostid')
                ->from('thread')
                ->whereNotNull('firstpostid');
        });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('threadid')
                ->label('الموضوع')
                ->options(fn() => Thread::where('visible', 1)->orderBy('dateline', 'desc')->limit(100)->pluck('title', 'threadid')->toArray())
                ->required()
                ->searchable(),

            Forms\Components\TextInput::make('username')
                ->label('اسم الكاتب')
                ->maxLength(100),

            Forms\Components\RichEditor::make('pagetext')
                ->label('محتوى الرد')
                ->required()
                ->columnSpanFull()
                ->formatStateUsing(function (?string $state) {
                    if (str_starts_with($state ?? '', '<!-- HTML -->')) {
                        return str_replace('<!-- HTML -->', '', $state);
                    }
                    return \App\Helpers\BBCodeParser::parse($state ?? '');
                })
                ->dehydrateStateUsing(fn(?string $state) => '<!-- HTML -->' . $state),

             Forms\Components\Hidden::make('dateline')
                ->default(time()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('postid')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('thread.title')
                    ->label('الموضوع')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('الكاتب')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pagetext')
                    ->label('المحتوى')
                    ->limit(60),

                Tables\Columns\IconColumn::make('visible')
                    ->label('مرئي')
                    ->boolean(),

                Tables\Columns\TextColumn::make('dateline')
                    ->label('التاريخ')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::createFromTimestamp($state)->format('Y/m/d') : '-')
                    ->sortable(),
            ])
            ->defaultSort('dateline', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('visible')
                    ->label('المرئية'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                 Tables\Actions\Action::make('hard_delete')
                     ->label('حذف فعلي')
                     ->icon('heroicon-o-trash')
                     ->color('danger')
                     ->visible(fn () => app(\App\Services\ModerationPermissionService::class)->isAdministrator(auth()->user()))
                     ->requiresConfirmation()
                     ->action(function (Post $record) {
                         app(ModerationActionService::class)->hardDeletePost($record, auth()->user());
                         Notification::make()->title('تم الحذف النهائي')->success()->send();
                     }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                     Tables\Actions\BulkAction::make('hard_delete')
                         ->label('حذف المحدد فعلياً')
                         ->color('danger')
                         ->visible(fn () => app(\App\Services\ModerationPermissionService::class)->isAdministrator(auth()->user()))
                         ->requiresConfirmation()
                         ->action(function (\Illuminate\Support\Collection $records) {
                             foreach ($records as $record) {
                                 app(ModerationActionService::class)->hardDeletePost($record, auth()->user());
                             }
                         }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
