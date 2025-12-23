<?php

namespace Tests\Unit\Factories;

use App\Models\User;
use App\Models\YnhServer;
use Illuminate\Support\Facades\Auth;

test('YnhServerFactory creates a YnhServer instance', function () {
    $server = YnhServer::factory()->create();
    expect($server)->toBeInstanceOf(YnhServer::class);
});

test('YnhServer belongs to user', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->for($user, 'user')->create();
    expect($server->user)->toBeInstanceOf(User::class);
    expect($server->user->id)->toBe($user->id);
});

test('YnhServer belongs to createdBy', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->for($user, 'createdBy')->create();
    expect($server->createdBy)->toBeInstanceOf(User::class);
    expect($server->createdBy->id)->toBe($user->id);
});

test('YnhServerFactory uses authenticated user when available', function () {
    $user = User::factory()->create();
    Auth::login($user);
    $server = YnhServer::factory()->create();
    expect($server->createdBy->id)->toBe($user->id);
    Auth::logout();

    asTenant1User();
    $server = YnhServer::factory()->create();
    expect($server->createdBy->id)->toBe(tenant1User()->id);
});

test('YnhServerFactory sets default attributes', function () {
    $server = YnhServer::factory()->create();
    expect($server->name)->toBeString();
    expect($server->ip_address)->toMatch('/^(\d{1,3}\.){3}\d{1,3}$/');
    expect($server->is_ready)->toBeTrue();
});

test('YnhServerFactory sets timestamps within 24 hours', function () {
    $server = YnhServer::factory()->create();
    expect($server->created_at)->not->toBeNull();
    expect($server->updated_at)->not->toBeNull();
    expect($server->created_at->diffInHours(now()))->toBeLessThanOrEqual(24);
});

test('YnhServerFactory can override name', function () {
    $server = YnhServer::factory()->create(['name' => 'test-server']);
    expect($server->name)->toBe('test-server');
});

test('YnhServerFactory can override ip_address', function () {
    $server = YnhServer::factory()->create(['ip_address' => '192.168.1.1']);
    expect($server->ip_address)->toBe('192.168.1.1');
});

test('YnhServerFactory can override is_ready', function () {
    $server = YnhServer::factory()->create(['is_ready' => false]);
    expect($server->is_ready)->toBeFalse();
});
