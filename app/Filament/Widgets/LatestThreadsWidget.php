<?php

namespace App\Filament\Widgets;

use App\Models\Thread;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestThreadsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Thread::query()
                    ->with('forum') // Eager Loading for performance
                    ->orderBy('dateline', 'desc')
                    ->limit(5)
            )
            ->heading('أحدث المواضيع في المنتدى')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الموضوع')
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('forum.title')
                    ->label('القسم')
                    ->badge(),
                Tables\Columns\TextColumn::make('postusername')
                    ->label('الكاتب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dateline')
                    ->label('وقت النشر')
                    ->formatStateUsing(fn($state) => $state ? Carbon::createFromTimestamp($state)->diffForHumans() : '-'),
                Tables\Columns\TextColumn::make('views')
                    ->label('المشاهدات')
                    ->numeric(),
            ])
            ->paginated(false);
    }
}
