<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendingThreadResource\Pages;
use App\Models\Forum;
use App\Models\Thread;
use App\Services\ModerationService;
use App\Services\ModerationActionService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PendingThreadResource extends Resource
{
    protected static ?string $model = Thread::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'مواضيع قيد المراجعة';
    protected static ?string $modelLabel = 'موضوع قيد المراجعة';
    protected static ?string $pluralModelLabel = 'مواضيع قيد المراجعة';
    protected static ?string $navigationGroup = 'المراجعة';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('visible', 0);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('threadid')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('forum.title')
                    ->label('القسم')
                    ->badge(),

                Tables\Columns\TextColumn::make('postusername')
                    ->label('الكاتب')
                    ->searchable(),

                Tables\Columns\TextColumn::make('dateline')
                    ->label('تاريخ الإنشاء')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::createFromTimestamp($state)->diffForHumans() : '-')
                    ->sortable(),
            ])
            ->defaultSort('dateline', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('forumid')
                    ->label('القسم')
                    ->options(fn() => Forum::active()->pluck('title', 'forumid')->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('موافقة ونشر')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('الموافقة على نشر الموضوع')
                    ->modalDescription('سيتم نشر الموضوع والموافقة على أول مشاركة فيه. هل أنت متأكد؟')
                    ->modalSubmitActionLabel('نعم، موافقة')
                    ->action(function (Thread $record) {
                        $ok = app(ModerationService::class)->approveThread($record->threadid);

                        Notification::make()
                            ->title($ok ? 'تمت الموافقة على الموضوع' : 'تعذرت الموافقة')
                            ->{$ok ? 'success' : 'danger'}()
                            ->send();
                    }),

                Tables\Actions\Action::make('preview')
                    ->label('معاينة المحتوى')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn(Thread $record) => view('filament.preview-content', [
                        'title' => $record->title,
                        'author' => $record->postusername,
                        'content' => $record->firstPost ? $record->firstPost->parsed_content : '',
                    ])),

                 Tables\Actions\Action::make('soft_delete')
                     ->label('رفض وحذف بسيط')
                     ->icon('heroicon-o-trash')
                     ->color('danger')
                     ->requiresConfirmation()
                     ->form([
                         \Filament\Forms\Components\Textarea::make('reason')
                             ->label('سبب الرفض')
                             ->default('رفض من المراجعة')
                             ->maxLength(125)
                             ->required(),
                     ])
                     ->action(function (Thread $record, array $data) {
                         app(ModerationActionService::class)->softDeleteThread($record, auth()->user(), $data['reason']);
                         Notification::make()->title('تم نقل الموضوع إلى المحذوفات')->success()->send();
                     }),
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
                                if ($service->approveThread($record->threadid)) {
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title('تمت الموافقة على ' . $count . ' مواضيع')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('bulk_reject')
                        ->label('رفض وحذف المحدد')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reason')
                                ->label('سبب الرفض')
                                ->default('رفض من المراجعة')
                                ->maxLength(125)
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $service = app(ModerationActionService::class);
                            $count = 0;
                            foreach ($records as $record) {
                                if ((int) $record->visible === 0) {
                                    $service->softDeleteThread($record, auth()->user(), $data['reason']);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title('تم رفض ' . $count . ' مواضيع')
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
            'index' => Pages\ListPendingThreads::route('/'),
        ];
    }
}
