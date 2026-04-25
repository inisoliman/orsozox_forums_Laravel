<?php

namespace App\Filament\Resources\EmailCampaignResource\Pages;

use App\Filament\Resources\EmailCampaignResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendCampaignEmailJob;

class EditEmailCampaign extends EditRecord
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getActions(): array
    {
        return [
            Actions\Action::make('send_campaign')
                ->label('إطلاق/استئناف الحملة الآن')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->action(function () {
                    $campaign = $this->record;
                    if ($campaign->status === 'sending' || $campaign->status === 'completed') {
                        $this->notify('warning', 'الحملة قيد الإرسال بالفعل أو منتهية.');
                        return;
                    }

                    // Count total target based on segment
                    $query = DB::table('email_subscribers')->where('email_status', 'valid');

                    if ($campaign->target_segment === 'active') {
                        $query->where('is_active', true);
                    }

                    $totalTarget = $query->count();

                    $campaign->update([
                        'status' => 'sending',
                        'started_at' => now(),
                        'total_recipients' => $totalTarget,
                    ]);

                    // Dispatch safely in batches
                    $query->select('id')->orderBy('id')->chunk(500, function ($subscribers) use ($campaign) {
                        foreach ($subscribers as $sub) {
                            SendCampaignEmailJob::dispatch($campaign->id, $sub->id)->onQueue('emails');
                        }
                    });

                    $this->notify('success', 'تم إطلاق الحملة وإرسالها لطابور الإرسال بخلفية السيرفر.');
                }),
            Actions\Action::make('pause_campaign')
                ->label('إيقاف مؤقت')
                ->color('danger')
                ->icon('heroicon-o-pause')
                ->action(function () {
                    $this->record->update(['status' => 'paused']);
                    $this->notify('success', 'تم إيقاف إرسال الحملة مؤقتاً.');
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
