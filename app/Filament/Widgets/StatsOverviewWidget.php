<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // استخدام الكاش لمدة ساعة (3600 ثانية) لتخفيف الضغط على خادم الاستضافة
        $userCount = Cache::remember('total_users_count', 3600, fn() => User::count());
        $threadCount = Cache::remember('total_threads_count', 3600, fn() => Thread::count());
        $postCount = Cache::remember('total_posts_count', 3600, fn() => Post::count());

        return [
            Stat::make('المواضيع', number_format($threadCount))
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->description('إجمالي المواضيع المكتوبة'),

            Stat::make('المشاركات والردود', number_format($postCount))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->description('إجمالي نشاط الأعضاء'),

            Stat::make('الأعضاء', number_format($userCount))
                ->icon('heroicon-o-users')
                ->color('primary')
                ->description('إجمالي الأعضاء المسجلين'),
        ];
    }
}
