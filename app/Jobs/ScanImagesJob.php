<?php

namespace App\Jobs;

use App\Models\ImageCache;
use App\Services\ImageValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScanImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    protected array $urls;

    public function __construct(array $urls)
    {
        $this->urls = $urls;
    }

    public function handle(ImageValidationService $validator): void
    {
        foreach ($this->urls as $url) {
            try {
                $validator->validate($url);
            } catch (\Throwable $e) {
                Log::debug('LIIMS scan job error', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }
    }
}
