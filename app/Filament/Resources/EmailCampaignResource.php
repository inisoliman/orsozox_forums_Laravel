<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailCampaignResource\Pages;
use App\Models\EmailCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'حملات البريد';
    protected static ?string $modelLabel = 'حملة بريدية';
    protected static ?string $pluralModelLabel = 'حملات البريد';
    protected static ?string $navigationGroup = 'التواصل والتسويق';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات الحملة')->schema([
                Forms\Components\TextInput::make('subject')
                    ->label('عنوان الرسالة (Subject)')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('target_segment')
                    ->label('الجمهور المستهدف')
                    ->options([
                        'all' => 'جميع المشتركين الصالحين',
                        'active' => 'المشتركين النشطين فقط',
                        'birthday' => 'تهنئة أعياد الميلاد (تلقائي)',
                    ])
                    ->default('all')
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('حالة الحملة')
                    ->options([
                        'draft' => 'مسودة',
                        'scheduled' => 'مجدولة',
                        'sending' => 'جاري الإرسال',
                        'paused' => 'متوقفة مؤقتاً',
                    ])
                    ->default('draft')
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('محتوى الرسالة')->schema([
                Forms\Components\RichEditor::make('content_html')
                    ->label('محتوى الرسالة (HTML)')
                    ->toolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
                        'h2',
                        'h3',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                    ])
                    ->helperText('يمكنك استخدام المتغيرات التالية: {{name}} , {{email}} , {{unsubscribe_link}}')
                    ->required()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('subject')->label('عنوان الحملة')->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'scheduled',
                        'primary' => 'sending',
                        'danger' => 'paused',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('sent_count')->label('تم الإرسال')->sortable(),
                Tables\Columns\TextColumn::make('failed_count')->label('فشل الإرسال')->sortable(),
                Tables\Columns\TextColumn::make('bounce_count')->label('المرتجعات (Bounces)'),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل / إدارة'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailCampaigns::route('/'),
            'create' => Pages\CreateEmailCampaign::route('/create'),
            'edit' => Pages\EditEmailCampaign::route('/{record}/edit'),
        ];
    }
}
