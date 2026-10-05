<?php

/**
 * Forum configuration — legacy vBulletin group mappings.
 *
 * These values are stored here (instead of hardcoding [5,6,7] across the app)
 * so they can be documented and changed in one place.
 */
return [
    'guest_usergroup_id' => 1,

    'administrator_usergroup_id' => 6,
    'super_moderator_usergroup_id' => 5,
    'moderator_usergroup_id' => 7,
    'moderator_usergroup_ids' => [5, 6, 7],
];
