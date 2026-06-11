<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TrafficSecurityReport extends Command
{
    protected $signature = 'traffic:security-report';

    protected $description = 'Print lightweight firewall/survival status counters for shared hosting diagnostics.';

    public function handle(): int
    {
        $hour = now()->format('YmdH');
        $classes = [
            'verified_search_engine',
            'fake_search_engine',
            'social_crawler',
            'ai_crawler',
            'aggressive_scraper',
            'suspicious_crawler',
            'human_or_unknown',
        ];

        $this->info('تقرير حماية المرور والزواحف - آخر ساعة');
        $this->line('Survival Mode: ' . (Cache::has('survival_mode_active') ? 'ACTIVE' : 'inactive'));
        $this->newLine();

        foreach ($classes as $class) {
            $this->line(str_pad($class, 28) . ': ' . (int) Cache::get('traffic_class:' . $hour . ':' . $class, 0));
        }

        return self::SUCCESS;
    }
}