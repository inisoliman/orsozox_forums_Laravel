<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumPermission extends Model
{
    public const CAN_VIEW = 1;
    public const CAN_POST_NEW = 32;
    public const CAN_REPLY = 64;

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
        return self::allows($forumid, $usergroupid, self::CAN_VIEW);
    }

    public static function canReply(int $forumid, int $usergroupid): bool
    {
        return self::allows($forumid, $usergroupid, self::CAN_REPLY);
    }

    public static function canViewAndReply(int $forumid, int $usergroupid): bool
    {
        return self::allows($forumid, $usergroupid, self::CAN_VIEW | self::CAN_REPLY);
    }

    public static function canPostNew(int $forumid, int $usergroupid): bool
    {
        return self::allows($forumid, $usergroupid, self::CAN_POST_NEW);
    }

    private static function allows(int $forumid, int $usergroupid, int $permissionBit): bool
    {
        if (in_array($usergroupid, config('forum.admin_usergroup_ids', [5, 6, 7]), true)) {
            return true;
        }

        $permission = self::where('forumid', $forumid)
            ->where('usergroupid', $usergroupid)
            ->first();

        return !$permission
            || (((int) $permission->forumpermissions & $permissionBit) === $permissionBit);
    }
}
