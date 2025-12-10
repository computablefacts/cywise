<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Hash;

function asTenant1User(): TestCase
{
    return test()->actingAs(tenant1User());
}

function asTenant1User2(): TestCase
{
    return test()->actingAs(tenant1User2());
}

function asTenant2User(): TestCase
{
    return test()->actingAs(tenant2User());
}

function tenant1User(): User
{
    Role::createRoles();

    return User::firstOrCreate(
        ['email' => 'user@tenant1.com'],
        [
            'name' => 'User Tenant 1',
            'email' => 'user@tenant1.com',
            'password' => Hash::make('passwordTenant1'),
            'verified' => true,
        ]
    );
}

function tenant1User2(): User
{
    Role::createRoles();

    return User::firstOrCreate(
        ['email' => 'user@tenant1.com'],
        [
            'name' => 'User2 Tenant 1',
            'email' => 'user2@tenant1.com',
            'password' => Hash::make('passwordTenant1'),
            'verified' => true,
            'tenant_id' => tenant1User()->tenant_id,
        ]
    );
}

function tenant2User(): User
{
    Role::createRoles();

    return User::firstOrCreate(
        ['email' => 'user@tenant2.com'],
        [
            'name' => 'User Tenant 2',
            'email' => 'user@tenant2.com',
            'password' => Hash::make('passwordTenant2'),
            'verified' => true,
        ]
    );
}
