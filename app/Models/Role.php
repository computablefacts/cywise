<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    public static function createRoles(): void
    {
        foreach (Role::ROLES as $role => $permissions) {

            Log::debug("Creating role {$role}...");

            /** @var Role $role */
            $role = Role::firstOrcreate(['name' => $role]);
            $role->permissions()->detach();
        }
        foreach (Role::ROLES as $role => $permissions) {

            /** @var Role $role */
            $role = Role::where('name', $role)->first();

            // Create missing permissions
            foreach ($permissions as $permission) {

                Log::debug("Creating permission {$permission}...");

                if (Str::startsWith($permission, 'call.')) {
                    $perm = Permission::firstOrCreate(
                        ['name' => $permission],
                        ['guard_name' => 'web'] // TODO : use api instead
                    );
                }
                if (Str::startsWith($permission, 'view.')) {
                    $perm = Permission::firstOrCreate(
                        ['name' => $permission],
                        ['guard_name' => 'web']
                    );
                }
            }

            // Attach permissions to role
            foreach ($permissions as $permission) {

                Log::debug("Attaching permission {$permission} to role {$role->name}...");

                $perm = Permission::where('name', $permission)->firstOrFail();
                $role->permissions()->syncWithoutDetaching($perm);
            }
        }
    }
}
