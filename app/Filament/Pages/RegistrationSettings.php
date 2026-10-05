<?php

namespace App\Filament\Pages;

use App\Http\Controllers\RegistrationController;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Models\SiteSetting;

class RegistrationSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'إعدادات التسجيل';
    protected static ?string $title = 'إعدادات تسجيل الأعضاء';
    protected static ?string $navigationGroup = 'الإدارة';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.registration-settings';

    public ?bool $registration_enabled = true;

    public function mount(): void
    {
        $this->registration_enabled = SiteSetting::getValue('registration.enabled', '1') === '1';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Toggle::make('registration_enabled')
                ->label('السماح بتسجيل أعضاء جدد')
                ->helperText('عند التعطيل: يُخفى رابط التسجيل ويُرفض endpoint التسجيل. الأدمن يستطيع إنشاء أعضاء من قائمة الأعضاء دائماً.')
                ->required(),
        ])->statePath('data');
    }

    public function save(): void
    {
        SiteSetting::setValue('registration.enabled', $this->registration_enabled ? '1' : '0');

        Notification::make()
            ->title($this->registration_enabled ? 'تم تفعيل التسجيل' : 'تم تعطيل التسجيل')
            ->success()
            ->send();
    }
}
