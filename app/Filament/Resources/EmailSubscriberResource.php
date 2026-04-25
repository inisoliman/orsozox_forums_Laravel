<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailSubscriberResource\Pages;
use App\Models\EmailSubscriber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailSubscriberResource extends Resource
{
    protected static ?string $model = EmailSubscriber::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'مشتركي البريد';
    protected static ?string $modelLabel = 'مشترك';
    protected static ?string $pluralModelLabel = 'قائمة المشتركين';
    protected static ?string $navigationGroup = 'التواصل والتسويق';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required(),

            Forms\Components\Select::make('email_status')
                ->label('حالة البريد')
                ->options([
                    'pending' => 'معلق (بانتظار الفحص)',
                    'valid' => 'صالح للاستخدام',
                    'invalid_format' => 'صيغة خاطئة',
                    'no_mx' => 'دومين غير موجود (No MX)',
                    'disposable' => 'إيميل وهمي (Disposable)',
                    'risky' => 'إيميل عام (Risky)',
                    'bounced' => 'مرتجع / لا يستلم رسائل',
                    'unsubscribed' => 'ألغى الاشتراك',
                ])
                ->default('pending')
                ->required(),

            Forms\Components\Toggle::make('is_active')
                ->label('نشط ضمن القوائم')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')->label('البريد')->searchable(),
                Tables\Columns\BadgeColumn::make('email_status')
                    ->label('الحالة')
                    ->colors([
                        'secondary' => 'pending',
                        'success' => 'valid',
                        'danger' => ['invalid_format', 'no_mx', 'disposable', 'bounced', 'unsubscribed'],
                        'warning' => 'risky',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('التفعيل')
                    ->boolean(),
                Tables\Columns\TextColumn::make('validation_score')
                    ->label('الثقة')
                    ->formatStateUsing(fn($state) => $state . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bounce_count')->label('Bounces')->sortable(),
                Tables\Columns\TextColumn::make('send_count')->label('تم الاستلام')->sortable(),
                Tables\Columns\TextColumn::make('last_validation_at')->label('آخر فحص')->dateTime('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('email_status')
                    ->label('فلترة بالحالة')
                    ->options([
                        'valid' => 'صالح للاستخدام فقط',
                        'bounced' => 'مرتجعات فقط',
                        'pending' => 'المعلقة للفحص',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailSubscribers::route('/'),
        ];
    }
}
