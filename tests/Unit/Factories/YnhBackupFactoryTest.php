<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\YnhBackup;
use App\Models\YnhServer;
use Illuminate\Support\Facades\Auth;

test('ynh backup can be created', function () {
    $backup = YnhBackup::factory()->create();
    expect($backup)->toBeInstanceOf(YnhBackup::class);
    expect($backup->id)->not->toBeNull();
});

test('ynh backup result is cast to array', function () {
    $backup = YnhBackup::factory()->create();
    $result = $backup->result;

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['system', 'apps']);
    expect($result['system'])->toBeArray();
    expect($result['apps'])->toBeArray();
    $this->assertDatabaseHas('ynh_backups', [
        'id' => $backup->id,
        'result' => json_encode($result),
    ]);
});

test('ynh backup belongs to user', function () {
    $user = User::factory()->create();
    $backup = YnhBackup::factory()->for($user, 'createdBy')->create();
    expect($backup->createdBy)->toBeInstanceOf(User::class);
    expect($backup->createdBy->id)->toBe($user->id);
});

test('ynh backup factory uses authenticated user when available', function () {
    $user = User::factory()->create();
    Auth::login($user);
    $backup = YnhBackup::factory()->create();
    expect($backup->createdBy->id)->toBe($user->id);
    Auth::logout();

    asTenant1User();
    $backup = YnhBackup::factory()->create();
    expect($backup->createdBy->id)->toBe(tenant1User()->id);
});

test('ynh backup belongs to server', function () {
    $server = YnhServer::factory()->create();
    $backup = YnhBackup::factory()->for($server, 'server')->create();
    expect($backup->server)->toBeInstanceOf(YnhServer::class);
    expect($backup->server->id)->toBe($server->id);
});

test('ynh backup with null created by', function () {
    $backup = YnhBackup::factory()->create(['created_by' => null]);
    expect($backup->createdBy)->toBeNull();
});

test('ynh backup with null storage path', function () {
    $backup = YnhBackup::factory()->create(['storage_path' => null]);
    expect($backup->storage_path)->toBeNull();
});

test('ynh backup with all success result', function () {
    $backup = YnhBackup::factory()->allSuccess()->create();
    $result = $backup->result;
    foreach ($result['system'] as $systemResult) {
        expect($systemResult)->toBe('Success');
    }
    foreach ($result['apps'] as $appResult) {
        expect($appResult)->toBe('Success');
    }
});

test('ynh backup with one system error result', function () {
    $backup = YnhBackup::factory()->oneSystemError()->create();
    $result = $backup->result;

    $systemErrors = 0;
    foreach ($result['system'] as $systemResult) {
        if ($systemResult === 'Error') {
            $systemErrors++;
        } else {
            expect($systemResult)->toBe('Success');
        }
    }
    expect($systemErrors)->toBe(1);

    foreach ($result['apps'] as $appResult) {
        expect($appResult)->toBe('Success');
    }
});

test('ynh backup with one app error result', function () {
    $backup = YnhBackup::factory()->oneAppError()->create();
    $result = $backup->result;

    foreach ($result['system'] as $systemResult) {
        expect($systemResult)->toBe('Success');
    }
    $appErrors = 0;

    foreach ($result['apps'] as $appResult) {
        if ($appResult === 'Error') {
            $appErrors++;
        } else {
            expect($appResult)->toBe('Success');
        }
    }
    expect($appErrors)->toBe(1);
});
