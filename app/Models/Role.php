<?php

namespace App\Models;

class Role extends \Spatie\Permission\Models\Role
{
    // https://devdojo.com/wave/docs/features/roles-permissions
    const string ADMIN = 'admin';
    const string REGISTERED = 'registered';
    const string ESSENTIAL_PLAN = 'essential';
    const string STANDARD_PLAN = 'standard';
    const string PREMIUM_PLAN = 'premium';
    const string CYBERBUDDY_ONLY = 'cyberbuddy only';
    const string CYBERBUDDY_ADMIN = 'cyberbuddy admin';
    const array ROLES = [
        self::ADMIN => [
            'view.iframes.*',
            'call.*',
        ],
        self::REGISTERED => [
            'view.iframes.*',
            'call.*',
        ],
        self::ESSENTIAL_PLAN => [
            'view.iframes.*',
            'call.*',
        ],
        self::STANDARD_PLAN => [
            'view.iframes.*',
            'call.*',
        ],
        self::PREMIUM_PLAN => [
            'view.iframes.*',
            'call.*',
        ],
        self::CYBERBUDDY_ADMIN => [
            'view.iframes.*',
            'call.*',
        ],
        self::CYBERBUDDY_ONLY => [
            'view.iframes.cyberbuddy',
            'call.cyberbuddy.ask',
        ],
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
