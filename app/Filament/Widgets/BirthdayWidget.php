<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BirthdayWidget extends BaseWidget
{
    protected static ?int $sort = 0; // يظهر أولاً (أعلى الصفحة)

    protected function getStats(): array
    {
        $todayMonth = Carbon::now()->format('m');
        $todayDay   = Carbon::now()->format('d');
        $birthdayPattern = $todayMonth . '-' . $todayDay . '-%';

        // أعضاء لديهم عيد ميلاد اليوم (vBulletin: birthday = mm-dd-yyyy)
        $todaysBirthdays = User::whereNotNull('birthday')
            ->where('birthday', 'like', $birthdayPattern)
            ->count();

        return [
            Stat::make('🎂 عيد ميلاد اليوم', number_format($todaysBirthdays))
                ->description('عدد الأعضاء المولودين في هذا اليوم')
                ->descriptionIcon('heroicon-m-cake')
                ->color('danger'),
        ];
    }
}
