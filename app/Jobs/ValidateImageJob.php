<?php

namespace App\Jobs;

use App\Models\ImageCache;
use App\Services\ImageValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected $imageId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $imageId)
    {
        $this->imageId = $imageId;
    }

    /**
     * Execute the job.
     */
    public function handle(ImageValidationService $validator): void
    {
        $image = ImageCache::find($this->imageId);

        if (!$image) {
            return;
        }

        // Only validate if it's pending or stale (needs re-check)
        if ($image->status === 'pending' || !$image->isFresh()) {
            $validator->validate($image->original_url);
        }
    }
}
