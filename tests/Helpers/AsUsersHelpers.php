<?php

use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Hash;

function asTenant1User(): TestCase
{
    Role::createRoles();

    $userTenant1 = \App\Models\User::firstOrCreate(
        ['email' => 'user@tenant1.com'],
        [
            'name' => 'User Tenant 1',
            'email' => 'user@tenant1.com',
            'password' => Hash::make('passwordTenant1'),
            'verified' => true,
        ]
    );

    return test()->actingAs($userTenant1);
}

function asTenant2User(): TestCase
{
    Role::createRoles();

    $userTenant2 = \App\Models\User::firstOrCreate(
        ['email' => 'user@tenant2.com'],
        [
            'name' => 'User Tenant 2',
            'email' => 'user@tenant2.com',
            'password' => Hash::make('passwordTenant2'),
            'verified' => true,
        ]
    );

    return test()->actingAs($userTenant2);
}

function tenant1UserId(): int
{
    return \App\Models\User::where('email', 'user@tenant1.com')->value('id');
}

function tenant2UserId(): int
{
    return \App\Models\User::where('email', 'user@tenant2.com')->value('id');
}
