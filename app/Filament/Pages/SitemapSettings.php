<?php

namespace App\Filament\Pages;

use App\Models\Forum;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * إعدادات خرائط الموقع (sitemap): اختيار الأقسام التي تُدرَج في ملف sitemap.xml.
 *
 * الأقسام غير المُختارة (مثل «المحذوفات» أو «المكرر») تُستثنى من:
 *  - sitemap-forums.xml (روابط الأقسام)
 *  - sitemap-threads-*.xml (روابط المواضيع داخلها)
 * عبر Thread::publiclyIndexable() + SitemapController::forums().
 */
class SitemapSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'إعدادات خرائط الموقع';
    protected static ?string $title = 'أقسام ملف sitemap';
    protected static ?string $navigationGroup = 'الإدارة';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.pages.sitemap-settings';

    /** @var array<int, string> */
    public array $included_forum_ids = [];

    public function mount(): void
    {
        $excluded = SiteSetting::excludedSitemapForumIds();
        $this->included_forum_ids = Forum::active()
            ->ordered()
            ->pluck('forumid')
            ->map(fn ($id) => (string) $id)
            ->reject(fn ($id) => in_array((int) $id, $excluded, true))
            ->values()
            ->all();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\CheckboxList::make('included_forum_ids')
                ->label('الأقسام المُدرَجة في ملف sitemap')
                ->helperText('أزل التحديد عن الأقسام التي لا تريد إدراجها (مثل قسم المحذوفات أو المكرر). المواضيع داخل الأقسام غير المُحدَّدة لن تظهر في sitemap.xml.')
                ->options(fn () => Forum::active()->ordered()->pluck('title', 'forumid')->toArray())
                ->columns(2)
                ->searchable()
                ->bulkToggleable(),
        ])->statePath('data');
    }

    public function save(): void
    {
        $allForumIds = Forum::active()->ordered()->pluck('forumid')->map(fn ($id) => (int) $id)->all();
        $included = array_map('intval', $this->included_forum_ids);
        $excluded = array_values(array_diff($allForumIds, $included));

        SiteSetting::setExcludedSitemapForumIds($excluded);

        // امسح كاش الـ sitemap حتى يُولَّد من جديد بدون الأقسام المستثناة فورًا.
        try {
            \App\Http\Controllers\SitemapController::clearCache();
        } catch (\Throwable $e) {
            report($e);
        }

        Notification::make()
            ->title('تم حفظ أقسام sitemap')
            ->body($excluded === []
                ? 'كل الأقسام النشطة مُدرَجة حاليًا في خرائط الموقع.'
                : 'عدد الأقسام المُستثناة: ' . count($excluded))
            ->success()
            ->send();
    }
}
