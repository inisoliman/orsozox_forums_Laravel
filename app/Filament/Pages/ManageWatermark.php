<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\SettingsService;

class ManageWatermark extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'إعدادات العلامة المائية';
    protected static ?string $title = 'إعدادات العلامة المائية والصور';
    protected static ?string $slug = 'manage-watermark';
    protected static string $view = 'filament.pages.manage-watermark';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);
        $this->form->fill([
            'image_watermark_enabled' => (bool) $settings->get('image_watermark_enabled', false),
            'image_watermark_type' => $settings->get('image_watermark_type', 'text'),
            'image_watermark_text' => $settings->get('image_watermark_text', ''),
            'image_watermark_image_path' => $settings->get('image_watermark_image_path', ''),
            'image_watermark_position' => $settings->get('image_watermark_position', 'bottom-right'),
            'image_watermark_opacity' => (int) $settings->get('image_watermark_opacity', 50),
            'image_watermark_font_size' => (int) $settings->get('image_watermark_font_size', 24),
            'image_watermark_margin' => (int) $settings->get('image_watermark_margin', 15),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('التحكم العام')
                    ->description('تفعيل أو تعطيل العلامة المائية على الصور المرفوعة')
                    ->schema([
                        Toggle::make('image_watermark_enabled')
                            ->label('تفعيل العلامة المائية')
                            ->helperText('عند التفعيل، ستُضاف العلامة المائية تلقائياً على كل صورة يتم رفعها من المحرر.')
                            ->default(false),

                        Select::make('image_watermark_type')
                            ->label('نوع العلامة المائية')
                            ->options([
                                'text' => '📝 نص فقط',
                                'image' => '🖼️ صورة فقط (PNG شفاف)',
                                'both' => '📝🖼️ نص + صورة معاً',
                            ])
                            ->default('text')
                            ->helperText('اختر نوع العلامة المائية المطلوب.'),
                    ])->columns(2),

                Section::make('إعدادات النص')
                    ->description('تخصيص نص العلامة المائية (يدعم العربية والإنجليزية)')
                    ->schema([
                        Textarea::make('image_watermark_text')
                            ->label('نص العلامة المائية')
                            ->placeholder('© منتدى أرثوذكس')
                            ->helperText('يدعم العربية والإنجليزية. سيتم استخدام خط Cairo.')
                            ->rows(2),

                        TextInput::make('image_watermark_font_size')
                            ->label('حجم الخط')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(72)
                            ->default(24)
                            ->suffix('px')
                            ->helperText('حجم خط العلامة المائية النصية (10-72).'),
                    ])->columns(2),

                Section::make('صورة العلامة المائية')
                    ->description('ارفع صورة PNG شفافة لاستخدامها كعلامة مائية')
                    ->schema([
                        FileUpload::make('image_watermark_image_path')
                            ->label('صورة العلامة المائية')
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->directory('watermarks')
                            ->disk('public')
                            ->helperText('يُفضّل صورة PNG شفافة الخلفية. الحجم الموصى به: 300×100 بكسل.'),
                    ]),

                Section::make('الموضع والشفافية')
                    ->description('تحكم في مكان وشفافية العلامة المائية')
                    ->schema([
                        Select::make('image_watermark_position')
                            ->label('موضع العلامة المائية')
                            ->options([
                                'top-left' => '↖ أعلى يسار',
                                'top-center' => '↑ أعلى وسط',
                                'top-right' => '↗ أعلى يمين',
                                'center-left' => '← وسط يسار',
                                'center' => '⊕ وسط',
                                'center-right' => '→ وسط يمين',
                                'bottom-left' => '↙ أسفل يسار',
                                'bottom-center' => '↓ أسفل وسط',
                                'bottom-right' => '↘ أسفل يمين',
                            ])
                            ->default('bottom-right'),

                        TextInput::make('image_watermark_opacity')
                            ->label('الشفافية')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(50)
                            ->suffix('%')
                            ->helperText('0 = شفاف تماماً، 100 = معتم تماماً'),

                        TextInput::make('image_watermark_margin')
                            ->label('الهامش')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(15)
                            ->suffix('px')
                            ->helperText('المسافة من حافة الصورة.'),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('💾 حفظ إعدادات العلامة المائية')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $settings = app(SettingsService::class);

        // Handle file upload path
        $imagePathValue = $state['image_watermark_image_path'] ?? '';
        if (is_array($imagePathValue)) {
            // Filament FileUpload returns array, get first item
            $imagePathValue = !empty($imagePathValue) ? array_values($imagePathValue)[0] : '';
        }

        $settings->setMany([
            'image_watermark_enabled' => $state['image_watermark_enabled'] ? '1' : '0',
            'image_watermark_type' => $state['image_watermark_type'] ?? 'text',
            'image_watermark_text' => $state['image_watermark_text'] ?? '',
            'image_watermark_image_path' => $imagePathValue,
            'image_watermark_position' => $state['image_watermark_position'] ?? 'bottom-right',
            'image_watermark_opacity' => (string) ($state['image_watermark_opacity'] ?? 50),
            'image_watermark_font_size' => (string) ($state['image_watermark_font_size'] ?? 24),
            'image_watermark_margin' => (string) ($state['image_watermark_margin'] ?? 15),
        ]);

        Notification::make()
            ->title('تم حفظ إعدادات العلامة المائية بنجاح ✅')
            ->success()
            ->send();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإدارة';
    }
}
