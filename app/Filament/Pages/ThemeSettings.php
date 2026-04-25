<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Services\SettingsService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\App;

class ThemeSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?string $title = 'Theme & Style Engine';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.theme-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $this->form->fill($settings->all());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Colors')
                    ->description('Manage primary and background colors for the frontend and admin panel.')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Primary Color')
                            ->default('#3b82f6'),
                        ColorPicker::make('background_color')
                            ->label('Background Color')
                            ->default('#ffffff'),
                        ColorPicker::make('admin_primary_color')
                            ->label('Admin Panel Primary Color')
                            ->default('#d97706'),
                    ])->columns(2),

                Section::make('Typography')
                    ->schema([
                        Select::make('font_family')
                            ->label('Main Font Family')
                            ->options([
                                "'Noto Kufi Arabic', sans-serif" => 'Noto Kufi Arabic (Default)',
                                "'Cairo', sans-serif" => 'Cairo',
                                "'Tajawal', sans-serif" => 'Tajawal',
                                "'Almarai', sans-serif" => 'Almarai',
                            ])
                            ->default("'Noto Kufi Arabic', sans-serif"),
                    ]),

                Section::make('Advanced')
                    ->description('Custom CSS injected directly into the pages.')
                    ->schema([
                        Textarea::make('custom_css')
                            ->label('Custom CSS Rules')
                            ->rows(10)
                            ->placeholder("body { \n  background: #000;\n}"),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $settings = App::make(SettingsService::class);
            $settings->setMultiple($data);

            Notification::make()
                ->success()
                ->title('Theme settings saved successfully!')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Failed to save settings: ' . $e->getMessage())
                ->send();
        }
    }
}
