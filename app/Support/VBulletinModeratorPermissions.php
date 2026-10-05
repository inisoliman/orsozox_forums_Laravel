<?php

namespace App\Support;

final class VBulletinModeratorPermissions
{
    public const EDIT_POSTS = 1;
    public const DELETE_POSTS = 2;
    public const OPEN_CLOSE = 4;
    public const EDIT_THREADS = 8;
    public const MANAGE_THREADS = 16;
    public const MODERATE_POSTS = 64;
    public const MODERATE_ATTACHMENTS = 128;
    public const MASS_MOVE = 256;
    public const MASS_PRUNE = 512;
    public const REMOVE_POSTS = 131072;

    public const EDIT_VISITOR_MESSAGES = 1;
    public const DELETE_VISITOR_MESSAGES = 2;
    public const REMOVE_VISITOR_MESSAGES = 4;
    public const MODERATE_VISITOR_MESSAGES = 8;

    public const GLOBAL_FORUM_ID = -1;
}
