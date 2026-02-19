<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ForumResource\Pages;
use App\Models\Forum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ForumResource extends Resource
{
    protected static ?string $model = Forum::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'الأقسام';
    protected static ?string $modelLabel = 'قسم';
    protected static ?string $pluralModelLabel = 'الأقسام';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('اسم القسم')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->label('الوصف')
                ->maxLength(1000),

            Forms\Components\TextInput::make('displayorder')
                ->label('ترتيب العرض')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('forumid')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable(),
                Tables\Columns\TextColumn::make('parent.title')->label('القسم الأب'),
                Tables\Columns\TextColumn::make('displayorder')->label('الترتيب')->sortable(),
                Tables\Columns\TextColumn::make('threadcount')->label('المواضيع')->numeric(),
            ])
            ->defaultSort('displayorder', 'asc')
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForums::route('/'),
            'edit' => Pages\EditForum::route('/{record}/edit'),
        ];
    }
}
