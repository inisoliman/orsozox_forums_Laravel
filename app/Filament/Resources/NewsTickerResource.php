<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsTickerResource\Pages;
use App\Models\NewsTicker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NewsTickerResource extends Resource
{
    protected static ?string $model = NewsTicker::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'شريط الأخبار';
    protected static ?string $modelLabel = 'خبر';
    protected static ?string $pluralModelLabel = 'شريط الأخبار';
    protected static ?string $navigationGroup = 'أدوات الموقع';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('content')
                    ->label('محتوى الخبر')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url')
                    ->label('الرابط (اختياري)')
                    ->url()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('الخبر مفعل ويظهر للزوار')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('الترتيب (الأولوية للرقم الأقل)')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->label('الخبر')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('url')
                    ->label('الرابط')
                    ->limit(30),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageNewsTickers::route('/'),
        ];
    }
}
