<?php
namespace App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected static ?string $title = 'إضافة عضو جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /* إنشاء عضو بصيغة vBulletin — يعمل حتى لو كان التسجيل العام مغلقاً */
        $plain = $data['new_password'] ?? null;
        unset($data['new_password']);

        if (empty($plain)) {
            $plain = bin2hex(random_bytes(8));
        }

        $salt = substr(md5((string) mt_rand()), 0, 30);

        return array_merge($data, [
            'password' => md5(md5($plain) . $salt),
            'salt' => $salt,
            'joindate' => time(),
            'lastvisit' => time(),
            'lastactivity' => time(),
            'posts' => 0,
            'reputation' => 10,
        ]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('تم إنشاء العضو بنجاح')
            ->success();
    }
}
