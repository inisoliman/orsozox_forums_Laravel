<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'الأعضاء';
    protected static ?string $modelLabel = 'عضو';
    protected static ?string $pluralModelLabel = 'الأعضاء';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('username')
                ->label('اسم المستخدم')
                ->required()
                ->maxLength(100),

            Forms\Components\TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->maxLength(100),

            Forms\Components\TextInput::make('new_password')
                ->label('كلمة المرور الجديدة')
                ->password()
                ->revealable()
                ->minLength(6)
                ->dehydrated(false)
                ->helperText('اتركها فارغة للحفاظ على كلمة المرور الحالية.'),

            Forms\Components\TextInput::make('usertitle')
                ->label('اللقب')
                ->maxLength(250),

            Forms\Components\TextInput::make('homepage')
                ->label('الموقع الشخصي')
                ->url()
                ->maxLength(255),

            Forms\Components\Select::make('usergroupid')
                ->label('المجموعة')
                ->options([
                    1 => 'غير مفعل',
                    2 => 'عضو',
                    3 => 'في انتظار تفعيل البريد',
                    5 => 'مشرف عام',
                    6 => 'مدير',
                    7 => 'مدير أعلى',
                ]),
        ]);
    }

    /**
     * معالجة حفظ العضو: تشفير كلمة المرور بصيغة vBulletin عند تعيينها.
     */
    public static function applyPassword(User $record, ?string $plain): void
    {
        if ($plain === null || $plain === '') {
            return;
        }

        $salt = $record->salt ?: substr(md5((string) mt_rand()), 0, 30);
        $record->update([
            'password' => md5(md5($plain) . $salt),
            'salt' => $salt,
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('userid')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('اسم المستخدم')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('البريد')
                    ->searchable(),

                Tables\Columns\TextColumn::make('posts')
                    ->label('المشاركات')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usergroupid')
                    ->label('المجموعة')
                    ->badge()
                    ->formatStateUsing(fn($state): string => match ((int) $state) {
                        1 => 'غير مفعل',
                        2 => 'عضو',
                        5 => 'مشرف',
                        6 => 'مدير',
                        7 => 'مدير أعلى',
                        default => "مجموعة {$state}",
                    }),

                Tables\Columns\TextColumn::make('joindate')
                    ->label('تاريخ التسجيل')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::createFromTimestamp($state)->format('Y/m/d') : '-')
                    ->sortable(),
            ])
            ->defaultSort('joindate', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('usergroupid')
                    ->label('المجموعة')
                    ->options([
                        2 => 'عضو',
                        5 => 'مشرف',
                        6 => 'مدير',
                        7 => 'مدير أعلى',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\Action::make('reset_password')
                    ->label('إعادة ضبط كلمة المرور')
                    ->icon('heroicon-o-key')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->label('كلمة المرور الجديدة')
                            ->password()
                            ->revealable()
                            ->minLength(6)
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        static::applyPassword($record, $data['new_password']);
                        \Filament\Notifications\Notification::make()
                            ->title('تم إعادة ضبط كلمة المرور')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
