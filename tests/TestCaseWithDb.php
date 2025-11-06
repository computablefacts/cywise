<?php

namespace Tests;

use App\Models\Alert;
use App\Models\Asset;
use App\Models\Attacker;
use App\Models\HiddenAlert;
use App\Models\Honeypot;
use App\Models\Port;
use App\Models\Role;
use App\Models\Scan;
use App\Models\Screenshot;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * To create the test database:
 * <pre>
 *     CREATE DATABASE tw_testdb DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 * </pre>
 *
 * To create the test user:
 * <pre>
 *     CREATE USER 'tw_testuser'@'localhost' IDENTIFIED BY 'z0rglub';
 *     GRANT ALL ON tw_testdb.* TO 'tw_testuser'@'localhost';
 *     FLUSH PRIVILEGES;
 * </pre>
 *
 * See https://dwij.net/how-to-speed-up-laravel-unit-tests-using-schemadump/
 */
abstract class TestCaseWithDb extends BaseTestCase
{
    use FastRefreshDatabase;

    protected User $userTenant1;

    protected User $userTenant2;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment() !== 'testing') {
            echo "The environment is not testing. I quit. This would likely destroy data.\n";
            exit(1);
        }

        Role::createRoles();

        $this->userTenant1 = \App\Models\User::firstOrCreate(
            ['email' => 'user@tenant1.com'],
            [
                'name' => 'User Tenant 1',
                'email' => 'user@tenant1.com',
                'password' => Hash::make('passwordTenant1'),
                'verified' => true,
            ]
        );

        $this->userTenant2 = \App\Models\User::firstOrCreate(
            ['email' => 'user@tenant2.com'],
            [
                'name' => 'User Tenant 2',
                'email' => 'user@tenant2.com',
                'password' => Hash::make('passwordTenant2'),
                'verified' => true,
            ]
        );

    }

    protected function tearDown(): void
    {
        Alert::whereNotNull('id')->delete();
        Asset::whereNotNull('id')->delete();
        Attacker::whereNotNull('id')->delete();
        HiddenAlert::whereNotNull('id')->delete();
        Honeypot::whereNotNull('id')->delete();
        Port::whereNotNull('id')->delete();
        Scan::whereNotNull('id')->delete();
        Screenshot::whereNotNull('id')->delete();
        parent::tearDown();
    }
}
