<?php

/**
 * Forum configuration — legacy vBulletin group mappings.
 *
 * These values are stored here (instead of hardcoding [5,6,7] across the app)
 * so they can be documented and changed in one place.
 */
return [
    'guest_usergroup_id' => 1,

    /*
     * Admin and moderator usergroup IDs (vBulletin legacy).
     * Users in these groups are considered super-admin in Filament.
     */
    'admin_usergroup_ids' => [5, 6, 7],
];
