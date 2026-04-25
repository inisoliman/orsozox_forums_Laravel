<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EmailValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    protected $subscriberIds;

    /**
     * Create a new job instance.
     * We pass chunked IDs to avoid memory bloat.
     */
    public function __construct(array $subscriberIds)
    {
        $this->subscriberIds = $subscriberIds;
    }

    /**
     * Execute the job.
     */
    public function handle(EmailValidationService $validator): void
    {
        // Safe memory: fetch only the required IDs
        $subscribers = DB::table('email_subscribers')
            ->whereIn('id', $this->subscriberIds)
            ->get();

        foreach ($subscribers as $subscriber) {
            try {
                $result = $validator->validate($subscriber->email);

                DB::table('email_subscribers')
                    ->where('id', $subscriber->id)
                    ->update([
                        'email_status' => $result['status'],
                        'validation_score' => $result['score'],
                        'last_validation_at' => now(),
                        'updated_at' => now(),
                    ]);
            } catch (\Exception $e) {
                Log::error("Validation failed for subscriber {$subscriber->id}", ['error' => $e->getMessage()]);
                // Keep pending to retry later
            }
        }
    }
}
