<?php

namespace App\Filament\Pages;

use App\Models\ImageCache;
use App\Jobs\ScanImagesJob;
use App\Models\Post;
use App\Services\SettingsService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ManageImages extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'إدارة الصور';
    protected static ?string $title = 'إدارة صور المنتدى - LIIMS';
    protected static ?string $navigationGroup = 'النظام';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.manage-images';

    public bool $proxyEnabled = false;
    public bool $autoCleanupEnabled = false;

    public function mount(): void
    {
        $settings = app(SettingsService::class);
        $this->proxyEnabled = $settings->get('image_proxy_enabled', '0') === '1';
        $this->autoCleanupEnabled = $settings->get('image_auto_cleanup', '0') === '1';
    }

    public function getStats(): array
    {
        $total = ImageCache::count();
        $valid = ImageCache::valid()->count();
        $broken = ImageCache::broken()->count();
        $pending = ImageCache::pending()->count();
        $stale = ImageCache::stale()->count();
        $lastScan = ImageCache::max('last_checked_at');

        return [
            'total' => $total,
            'valid' => $valid,
            'broken' => $broken,
            'pending' => $pending,
            'stale' => $stale,
            'percentage_broken' => $total > 0 ? round(($broken / $total) * 100, 1) : 0,
            'health_score' => $total > 0 ? round(($valid / $total) * 100, 1) : 100,
            'last_scan' => $lastScan,
        ];
    }

    public function toggleProxy(): void
    {
        $settings = app(SettingsService::class);
        $this->proxyEnabled = !$this->proxyEnabled;
        $settings->set('image_proxy_enabled', $this->proxyEnabled ? '1' : '0');

        Notification::make()
            ->title($this->proxyEnabled ? 'تم تفعيل بروكسي الصور' : 'تم تعطيل بروكسي الصور')
            ->success()
            ->send();
    }

    public function toggleAutoCleanup(): void
    {
        $settings = app(SettingsService::class);
        $this->autoCleanupEnabled = !$this->autoCleanupEnabled;
        $settings->set('image_auto_cleanup', $this->autoCleanupEnabled ? '1' : '0');

        Notification::make()
            ->title($this->autoCleanupEnabled ? 'تم تفعيل التنظيف التلقائي' : 'تم تعطيل التنظيف التلقائي')
            ->success()
            ->send();
    }

    public function runQuickScan(): void
    {
        // Scan last 100 posts for external images
        $urls = $this->extractExternalImages(100);

        if (empty($urls)) {
            Notification::make()->title('لم يتم العثور على صور خارجية.')->warning()->send();
            return;
        }

        // Dispatch in batches
        foreach (array_chunk($urls, 10) as $chunk) {
            ScanImagesJob::dispatch($chunk);
        }

        Notification::make()
            ->title('تم بدء المسح')
            ->body('تم إرسال ' . count($urls) . ' صورة للفحص.')
            ->success()
            ->send();
    }

    public function exportBrokenCsv()
    {
        $broken = ImageCache::broken()->get(['original_url', 'response_code', 'last_checked_at']);

        if ($broken->isEmpty()) {
            Notification::make()->title('لا توجد صور مكسورة للتصدير.')->warning()->send();
            return;
        }

        $csv = "URL,Response Code,Last Checked\n";
        foreach ($broken as $row) {
            $csv .= "\"{$row->original_url}\",{$row->response_code},{$row->last_checked_at}\n";
        }

        $filename = 'broken_images_' . date('Y-m-d') . '.csv';
        $path = storage_path('app/' . $filename);
        file_put_contents($path, $csv);

        Notification::make()
            ->title('تم تصدير القائمة')
            ->body("تم حفظ الملف: storage/app/{$filename}")
            ->success()
            ->send();
    }

    private function extractExternalImages(int $limit): array
    {
        $urls = [];

        Post::select('pagetext')
            ->orderBy('postid', 'desc')
            ->limit($limit)
            ->chunk(50, function ($posts) use (&$urls) {
                foreach ($posts as $post) {
                    $text = $post->pagetext ?? '';
                    preg_match_all('/\[img\](https?:\/\/[^\[]+)\[\/img\]/i', $text, $bb);
                    preg_match_all('/<img[^>]+src=["\']?(https?:\/\/[^"\'>\s]+)["\']?/i', $text, $html);
                    $found = array_merge($bb[1] ?? [], $html[1] ?? []);
                    foreach ($found as $url) {
                        $host = parse_url(trim($url), PHP_URL_HOST);
                        if ($host && !str_contains($host, 'orsozox.com')) {
                            $urls[] = trim($url);
                        }
                    }
                }
            });

        return array_unique($urls);
    }
}
