<?php
namespace App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    protected static ?string $title = 'تعديل العضو';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /* حقل new_password غير مخزّن مباشرة — يُعالج بعد الحفظ */
        $this->newPassword = $data['new_password'] ?? null;
        unset($data['new_password']);

        return $data;
    }

    protected function afterSave(): void
    {
        if (! empty($this->newPassword)) {
            UserResource::applyPassword($this->record, $this->newPassword);
        }
    }
}
