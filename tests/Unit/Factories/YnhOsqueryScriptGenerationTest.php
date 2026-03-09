<?php

namespace Tests\Unit\Factories;

use App\Enums\OsqueryPlatformEnum;
use App\Models\User;
use App\Models\YnhOsquery;
use App\Models\YnhServer;

// ─── monitorLinuxServer ───────────────────────────────────────────────────────

test('monitorLinuxServer returns a bash script', function () {
    $user = User::factory()->create(['performa_domain' => null]);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);

    $script = YnhOsquery::monitorLinuxServer($server);

    expect($script)->toStartWith('#!/bin/bash');
});

test('monitorLinuxServer contains the server secret', function () {
    $user = User::factory()->create(['performa_domain' => null]);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);

    $script = YnhOsquery::monitorLinuxServer($server);

    expect($script)->toContain($server->secret);
});

test('monitorLinuxServer contains the app url', function () {
    $user = User::factory()->create(['performa_domain' => null]);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);

    $script = YnhOsquery::monitorLinuxServer($server);

    expect($script)->toContain(app_url());
});

test('monitorLinuxServer does not contain performa section when user has no performa domain', function () {
    $user = User::factory()->create(['performa_domain' => null]);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);

    $script = YnhOsquery::monitorLinuxServer($server);

    expect($script)->not->toContain('performa-satellite');
});

test('monitorLinuxServer contains performa install section when user has a performa domain', function () {
    $user = User::factory()->create(['performa_domain' => 'performa.example.com']);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);

    $script = YnhOsquery::monitorLinuxServer($server);

    expect($script)->toContain('Install performa-satellite');
    expect($script)->toContain('Update performa-satellite configuration');
});

// ─── monitorWindowsServer ─────────────────────────────────────────────────────

test('monitorWindowsServer returns a powershell script', function () {
    $user = User::factory()->create(['performa_domain' => null]);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'name' => 'my-windows-server',
        'platform' => OsqueryPlatformEnum::WINDOWS,
    ]);

    $script = YnhOsquery::monitorWindowsServer($server);

    expect($script)->toContain('CreateOrUpdate-ScheduledTask');
});

test('monitorWindowsServer contains the server secret', function () {
    $user = User::factory()->create(['performa_domain' => null]);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'platform' => OsqueryPlatformEnum::WINDOWS,
    ]);

    $script = YnhOsquery::monitorWindowsServer($server);

    expect($script)->toContain($server->secret);
});

test('monitorWindowsServer contains the app url', function () {
    $user = User::factory()->create(['performa_domain' => null]);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'platform' => OsqueryPlatformEnum::WINDOWS,
    ]);

    $script = YnhOsquery::monitorWindowsServer($server);

    expect($script)->toContain(app_url());
});

test('monitorWindowsServer does not contain performa section when user has no performa domain', function () {
    $user = User::factory()->create(['performa_domain' => null]);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'platform' => OsqueryPlatformEnum::WINDOWS,
    ]);

    $script = YnhOsquery::monitorWindowsServer($server);

    expect($script)->not->toContain('performa-satellite-win-x64.exe');
});

test('monitorWindowsServer contains performa install section when user has a performa domain', function () {
    $user = User::factory()->create(['performa_domain' => 'performa.example.com']);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'name' => 'my-windows-server',
        'platform' => OsqueryPlatformEnum::WINDOWS,
    ]);

    $script = YnhOsquery::monitorWindowsServer($server);

    expect($script)
        ->toContain('Install or update performa-satellite')
        ->toContain('Update performa-satellite configuration')
        ->toContain('Send metric to performa every minute')
        ->toContain('my-windows-server'); // server name appears in --hostname argument
});

// ─── monitorLocalMetricsWindows ───────────────────────────────────────────────

test('monitorLocalMetricsWindows returns a powershell script with expected functions', function () {
    $user = User::factory()->create(['performa_domain' => null]);
    $server = YnhServer::factory()->for($user, 'user')->create([
        'platform' => OsqueryPlatformEnum::WINDOWS,
    ]);

    $script = YnhOsquery::monitorLocalMetricsWindows($server);

    expect($script)
        ->toContain('function Get-CpuMetrics()')
        ->toContain('function Get-DiskMetrics()')
        ->toContain('function Get-MemoryMetrics()')
        ->toContain('function Generate-OsqueryJson');
});
