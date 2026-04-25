<?php

namespace App\Filament\Widgets;

use App\Models\EmailSubscriber;
use App\Models\EmailCampaign;
use App\Models\EmailLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class EmailStatisticsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalValid = EmailSubscriber::where('email_status', 'valid')->count();
        $totalBounced = EmailSubscriber::where('email_status', 'bounced')->count();
        $totalUnsub = EmailSubscriber::where('email_status', 'unsubscribed')->count();

        $campaignsSent = EmailCampaign::whereIn('status', ['sending', 'completed'])->count();
        $emailsSentToday = Cache::get('emails_sent_today', 0);
        $dailyQuota = Cache::get('email_daily_quota', 500);

        return [
            Stat::make('المشتركين الصالحين (Valid)', number_format($totalValid))
                ->description('إيميلات جاهزة للاستلام')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('المرتجعات (Bounces)', number_format($totalBounced))
                ->description('تم الحجب تلقائياً')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('إلغاء الاشتراك', number_format($totalUnsub))
                ->description('ألغوا اشتراكهم')
                ->color('warning'),

            Stat::make('سعة الإرسال اليومي', number_format($emailsSentToday) . ' / ' . number_format($dailyQuota))
                ->description('سرعة الإحماء الحالية')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($emailsSentToday >= $dailyQuota ? 'danger' : 'primary'),

            Stat::make('حملات نشطة ومنتهية', number_format($campaignsSent))
                ->description('إجمالي الحملات')
                ->color('primary'),
        ];
    }
}
