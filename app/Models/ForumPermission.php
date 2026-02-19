<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumPermission extends Model
{
    protected $table = 'forumpermission';
    protected $primaryKey = 'forumpermissionid';
    public $timestamps = false;

    protected $fillable = [
        'forumid',
        'usergroupid',
        'forumpermissions', // Bitfield
    ];

    /**
     * التحقق من إمكانية رؤية القسم
     *
     * منطق vBulletin:
     * - إذا لا يوجد سجل → مسموح (الافتراضي)
     * - إذا وجد سجل و Bit 1 مفعّل → مسموح
     * - إذا وجد سجل و Bit 1 غير مفعّل → محجوب
     */
    public static function canView(int $forumid, int $usergroupid): bool
    {
        // المشرفون والإدارة يرون كل شيء
        if (in_array($usergroupid, [5, 6, 7])) {
            return true;
        }

        $permission = self::where('forumid', $forumid)
            ->where('usergroupid', $usergroupid)
            ->first();

        // لا يوجد سجل = مسموح (سلوك vBulletin الافتراضي)
        if (!$permission) {
            return true;
        }

        // Bit 1 = canview
        return (bool) ((int) $permission->forumpermissions & 1);
    }
}
