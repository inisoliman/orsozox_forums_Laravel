<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ThreadResource\Pages;
use App\Models\Thread;
use App\Models\Forum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ThreadResource extends Resource
{
    protected static ?string $model = Thread::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'المواضيع';
    protected static ?string $modelLabel = 'موضوع';
    protected static ?string $pluralModelLabel = 'المواضيع';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('عنوان الموضوع')
                ->required()
                ->maxLength(255)
                ->columnSpanFull()
                // Inject custom JS via a view to handle frontend duplicate checks
                ->extraInputAttributes(['id' => 'thread-title-input']),

            Forms\Components\ViewField::make('duplicate_checker')
                ->view('filament.forms.components.duplicate-checker')
                ->columnSpanFull(),

            Forms\Components\Select::make('forumid')
                ->label('القسم')
                ->options(fn() => Forum::active()->pluck('title', 'forumid')->toArray())
                ->required()
                ->searchable(),

            Forms\Components\TextInput::make('postusername')
                ->label('اسم الكاتب')
                ->maxLength(100),

            Forms\Components\Toggle::make('open')
                ->label('مفتوح للردود')
                ->default(true),

            Forms\Components\Toggle::make('visible')
                ->label('مرئي')
                ->default(true),

            Forms\Components\Hidden::make('dateline')
                ->default(time()),

            Forms\Components\Hidden::make('lastpost')
                ->default(time()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('threadid')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('forum.title')
                    ->label('القسم')
                    ->badge(),

                Tables\Columns\TextColumn::make('postusername')
                    ->label('الكاتب')
                    ->searchable(),

                Tables\Columns\TextColumn::make('views')
                    ->label('المشاهدات')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('replycount')
                    ->label('الردود')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('open')
                    ->label('مفتوح')
                    ->boolean(),

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
                Tables\Filters\SelectFilter::make('forumid')
                    ->label('القسم')
                    ->options(fn() => Forum::active()->pluck('title', 'forumid')->toArray()),

                Tables\Filters\TernaryFilter::make('open')
                    ->label('حالة الموضوع')
                    ->trueLabel('مفتوح')
                    ->falseLabel('مغلق'),

                Tables\Filters\TernaryFilter::make('visible')
                    ->label('المرئية')
                    ->trueLabel('مرئي')
                    ->falseLabel('مخفي'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
                Tables\Actions\Action::make('toggle_open')
                    ->label(fn(Thread $record) => $record->open ? 'إغلاق' : 'فتح')
                    ->icon(fn(Thread $record) => $record->open ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->action(function (Thread $record) {
                        $record->update(['open' => !$record->open]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListThreads::route('/'),
            'create' => Pages\CreateThread::route('/create'),
            'edit' => Pages\EditThread::route('/{record}/edit'),
        ];
    }
}
