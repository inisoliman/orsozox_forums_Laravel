<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendingVisitorMessageResource\Pages;
use App\Models\VisitorMessage;
use App\Services\ModerationService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PendingVisitorMessageResource extends Resource
{
    protected static ?string $model = VisitorMessage::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'رسائل زوار قيد المراجعة';
    protected static ?string $modelLabel = 'رسالة زائر قيد المراجعة';
    protected static ?string $pluralModelLabel = 'رسائل زوار قيد المراجعة';
    protected static ?string $navigationGroup = 'المراجعة';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('state', 'moderation');
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
                Tables\Columns\TextColumn::make('vmid')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('postusername')
                    ->label('المرسل')
                    ->searchable(),

                Tables\Columns\TextColumn::make('profileOwner.username')
                    ->label('على صفحة'),

                Tables\Columns\TextColumn::make('pagetext')
                    ->label('المحتوى')
                    ->limit(60),

                Tables\Columns\TextColumn::make('dateline')
                    ->label('التاريخ')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::createFromTimestamp($state)->diffForHumans() : '-')
                    ->sortable(),
            ])
            ->defaultSort('dateline', 'desc')
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('موافقة ونشر')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('الموافقة على نشر الرسالة')
                    ->modalSubmitActionLabel('نعم، موافقة')
                    ->action(function (VisitorMessage $record) {
                        $ok = app(ModerationService::class)->approveVisitorMessage($record->vmid);
                        Notification::make()
                            ->title($ok ? 'تم نشر الرسالة' : 'تعذرت الموافقة')
                            ->{$ok ? 'success' : 'danger'}()
                            ->send();
                    }),

                Tables\Actions\Action::make('preview')
                    ->label('معاينة المحتوى')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn(VisitorMessage $record) => view('filament.preview-content', [
                        'title' => $record->postusername . ' ← صفحة ' . ($record->profileOwner->username ?? $record->userid),
                        'author' => $record->postusername,
                        'content' => $record->parsed_content,
                    ])),

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
                                if ($service->approveVisitorMessage($record->vmid)) {
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title('تم نشر ' . $count . ' رسائل')
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
            'index' => Pages\ListPendingVisitorMessages::route('/'),
        ];
    }
}
