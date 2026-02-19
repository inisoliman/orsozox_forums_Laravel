<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'vbulletin',
        ],
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'vbulletin',
        ],
    ],

    'providers' => [
        'vbulletin' => [
            'driver' => 'vbulletin',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'vbulletin',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
