<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendingPostResource\Pages;
use App\Models\Post;
use App\Models\Thread;
use App\Services\ModerationService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PendingPostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';
    protected static ?string $navigationLabel = 'ردود قيد المراجعة';
    protected static ?string $modelLabel = 'رد قيد المراجعة';
    protected static ?string $pluralModelLabel = 'ردود قيد المراجعة';
    protected static ?string $navigationGroup = 'المراجعة';
    protected static ?int $navigationSort = 2;

    /**
     * الردود فقط (بدون المشاركة الأولى للموضوع) وغير الموافق عليها.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('visible', 0)
            ->whereNotIn('postid', function ($query) {
                $query->select('firstpostid')
                    ->from('thread')
                    ->whereNotNull('firstpostid');
            });
    }

    public static function canCreate(): bool
    {
        return false;
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
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('username')
                    ->label('الكاتب')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pagetext')
                    ->label('المحتوى')
                    ->limit(60)
                    ->html(false),

                Tables\Columns\TextColumn::make('dateline')
                    ->label('تاريخ الإضافة')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::createFromTimestamp($state)->diffForHumans() : '-')
                    ->sortable(),
            ])
            ->defaultSort('dateline', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('threadid')
                    ->label('الموضوع')
                    ->options(fn() => Thread::where('visible', 1)->orderBy('dateline', 'desc')->limit(100)->pluck('title', 'threadid')->toArray())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('موافقة ونشر')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('الموافقة على نشر الرد')
                    ->modalDescription('سيتم نشر الرد وزيادة عدد ردود الموضوع. هل أنت متأكد؟')
                    ->modalSubmitActionLabel('نعم، موافقة')
                    ->action(function (Post $record) {
                        $ok = app(ModerationService::class)->approvePost($record->postid);

                        Notification::make()
                            ->title($ok ? 'تمت الموافقة على الرد' : 'تعذرت الموافقة')
                            ->{$ok ? 'success' : 'danger'}()
                            ->send();
                    }),

                Tables\Actions\Action::make('preview')
                    ->label('معاينة المحتوى')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn(Post $record) => view('filament.preview-content', [
                        'title' => $record->thread->title ?? 'رد',
                        'author' => $record->username,
                        'content' => $record->parsed_content,
                    ])),

                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\Action::make('bulk_approve')
                        ->label('موافقة على المحدد')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $service = app(ModerationService::class);
                            $count = 0;
                            foreach ($records as $record) {
                                if ($service->approvePost($record->postid)) {
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title('تمت الموافقة على ' . $count . ' ردود')
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListPendingPosts::route('/'),
        ];
    }
}