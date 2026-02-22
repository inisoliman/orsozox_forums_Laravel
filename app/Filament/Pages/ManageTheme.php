<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\ThemeSettings;

class ManageTheme extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel = 'إعدادات المظهر';
    protected static ?string $title = 'إعدادات مظهر المنتدى';
    protected static ?string $slug = 'manage-theme';
    protected static string $view = 'filament.pages.manage-theme';

    public ?array $data = [];

    public function mount(ThemeSettings $settings): void
    {
        $this->form->fill($settings->all());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('عام')
                            ->schema([
                                TextInput::make('site_name')
                                    ->label('اسم الموقع')
                                    ->placeholder('منتدى أرثوذكس'),
                                Textarea::make('site_description')
                                    ->label('وصف الموقع (SEO)')
                                    ->rows(3),
                                Toggle::make('maintenance_mode')
                                    ->label('وضع الصيانة')
                                    ->helperText('عند التفعيل سيظهر للمستخدمين صفحة صيانة.'),
                                Toggle::make('news_ticker_enabled')
                                    ->label('إظهار شريط الأخبار')
                                    ->default(true)
                                    ->helperText('تفعيل أو تعطيل شريط الأخبار العاجلة في كامل المنتدى.'),
                            ]),

                        Tabs\Tab::make('الإعلانات')
                            ->schema([
                                Toggle::make('ads.enabled')
                                    ->label('تفعيل الإعلانات')
                                    ->default(true),

                                Textarea::make('ads.excluded_forums')
                                    ->label('أرقام الأقسام المستثناة من الإعلانات')
                                    ->helperText('اكتب أرقام الأقسام مفصولة بفاصلة. مثال: 156,38,44')
                                    ->default('156,38,44,43,62,63,83,113,64,105,65,112,66,107,68,108,67,159,160,69,70,71,72,104,73,74,75,76,77,121,45,156,118,55,45,118,117,46,47,155,48,49,50,51,52,53,55,54,39,40,41,42,43,44'),

                                Section::make('أماكن الإعلانات')
                                    ->schema([
                                        Textarea::make('ads.header_code')
                                            ->label('إعلان الهيدر (Header)')
                                            ->helperText('يظهر أسفل القائمة العلوية. المقاس الموصى به: 728x90')
                                            ->rows(4),

                                        Textarea::make('ads.sidebar_code')
                                            ->label('إعلان الشريط الجانبي (Sidebar)')
                                            ->helperText('يظهر في الشريط الجانبي. المقاس الموصى به: 300x250')
                                            ->rows(4),

                                        Textarea::make('ads.feed_code')
                                            ->label('إعلان بين الأقسام (Feed)')
                                            ->helperText('يظهر بين الأقسام في الرئيسية. المقاس الموصى به: متجاوب')
                                            ->rows(4),
                                    ]),
                            ]),

                        Tabs\Tab::make('أكواد مخصصة')
                            ->schema([
                                Textarea::make('scripts.header')
                                    ->label('أكواد الهيدر (Head)')
                                    ->helperText('مثل Google Analytics. يوضع قبل إغلاق </head>')
                                    ->rows(5),

                                Textarea::make('scripts.footer')
                                    ->label('أكواد الفوتر (Footer)')
                                    ->helperText('يوضع قبل إغلاق </body>')
                                    ->rows(5),

                                Textarea::make('css.custom')
                                    ->label('CSS مخصص')
                                    ->rows(10),
                            ]),

                        Tabs\Tab::make('الفوتر والتواصل')
                            ->schema([
                                Section::make('روابط التواصل الاجتماعي')
                                    ->schema([
                                        TextInput::make('social.facebook')->label('Facebook URL')->url(),
                                        TextInput::make('social.twitter')->label('Twitter / X URL')->url(),
                                        TextInput::make('social.youtube')->label('YouTube URL')->url(),
                                        TextInput::make('social.instagram')->label('Instagram URL')->url(),
                                        TextInput::make('social.telegram')->label('Telegram URL')->url(),
                                    ])->columns(3),

                                Textarea::make('footer.about')
                                    ->label('نص "عن الموقع"')
                                    ->rows(3),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الإعدادات')
                ->submit('save'),
        ];
    }

    public function save(ThemeSettings $settings): void
    {
        $settings->setAll($this->form->getState());
        $settings->save();

        Notification::make()
            ->title('تم حفظ الإعدادات بنجاح')
            ->success()
            ->send();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإدارة';
    }
}
