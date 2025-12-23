<?php

namespace Tests\Feature;

use App\Enums\OsqueryPlatformEnum;
use App\Enums\ServerStatusEnum;
use App\Models\User;
use App\Models\YnhServer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;

describe('expandIp()', function () {

    it('can expand ipv6 address', function () {
        $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        $expanded = YnhServer::expandIp($ipv6);
        expect($expanded)->toContain(':');
    });

    it('returns ipv4 unchanged', function () {
        $ipv4 = '192.168.1.1';
        $result = YnhServer::expandIp($ipv4);
        expect($result)->toBe($ipv4);
    });

});

describe('forUser()', function () {

    it('returns servers', function () {
        $user = User::factory()->create(['tenant_id' => null]);
        $server = YnhServer::factory()->create(['user_id' => $user->id]);

        $servers = YnhServer::forUser($user);
        expect($servers->contains($server))->toBeTrue();
    });

    it('filters ready servers', function () {
        $user = User::factory()->create(['tenant_id' => null]);
        YnhServer::factory()->create(['user_id' => $user->id, 'is_ready' => true]);
        YnhServer::factory()->create(['user_id' => $user->id, 'is_ready' => false]);

        $servers = YnhServer::forUser($user, true);
        expect($servers->count())->toBe(1);
    });

    it('returns servers of the user tenant only', function () {
        asTenant1User();
        YnhServer::factory()->create();
        asTenant2User();
        YnhServer::factory(2)->create();

        $servers = YnhServer::forUser(tenant1User());
        expect($servers->count())->toBe(1);
        $servers = YnhServer::forUser(tenant2User());
        expect($servers->count())->toBe(2);
        $allServers = YnhServer::withoutGlobalScope('tenant_scope_2')->get();
        expect($allServers->count())->toBe(3);
    });

});

describe('isYunoHost()', function () {

    test('is true when not frozen and not added with curl', function () {
        $server = YnhServer::factory()->create(['added_with_curl' => false, 'is_frozen' => false]);
        expect($server->isYunoHost())->toBeTrue();
    });

    test('is false when frozen', function () {
        $server = YnhServer::factory()->create(['is_frozen' => true, 'added_with_curl' => false]);
        expect($server->isYunoHost())->toBeFalse();
    });

    test('is false when added with curl', function () {
        $server = YnhServer::factory()->create(['added_with_curl' => true, 'is_frozen' => false]);
        expect($server->isYunoHost())->toBeFalse();
    });

});

describe('ipv6()', function () {

    test('ipv6 returns null when unavailable', function () {
        $server = YnhServer::factory()->create(['ip_address_v6' => '<unavailable>']);
        expect($server->ipv6())->toBeNull();
    });

    test('ipv6 returns address', function () {
        $ipv6 = '2001:db8::1';
        $server = YnhServer::factory()->create(['ip_address_v6' => $ipv6]);
        expect($server->ipv6())->toBe($ipv6);
    });

});

describe('status()', function () {

    test('status returns unknown when frozen', function () {
        $server = YnhServer::factory()->create(['is_frozen' => true, 'is_ready' => true]);
        expect($server->status())->toBe(ServerStatusEnum::UNKNOWN);
    });

    test('status returns down when not ready', function () {
        $server = YnhServer::factory()->create(['is_ready' => false]);
        expect($server->status())->toBe(ServerStatusEnum::DOWN);
    });

    // test('status returns running when last heartbeat within 10 minutes', function () {
    //     $server = YnhServer::factory()->create(['is_ready' => true]);

    //     $serverMock = Mockery::mock($server);
    //     $serverMock->shouldReceive('lastHeartbeat')->andReturn(Carbon::now()->subMinutes(8));

    //     expect($serverMock->status())->toBe(ServerStatusEnum::RUNNING);
    // });

});

describe('attributes', function () {

    test('hidden attributes', function () {
        Config::set('towerify.hasher.nonce', 'test_nonce');
        $server = YnhServer::factory()->create(['ssh_private_key' => 'secret', 'secret' => 'hidden']);
        $data = $server->toArray();

        expect($data)->not->toHaveKey('ssh_private_key');
        expect($data)->not->toHaveKey('secret');
    });

    test('casts boolean attributes', function () {
        $server = YnhServer::factory()->create([
            'updated' => true,
            'is_ready' => false,
            'is_frozen' => true,
            'added_with_curl' => false,
        ]);

        expect($server->updated)->toBeBool();
        expect($server->is_ready)->toBeBool();
        expect($server->is_frozen)->toBeBool();
        expect($server->added_with_curl)->toBeBool();
    });

    test('casts platform enum', function () {
        $server = YnhServer::factory()->create(['platform' => OsqueryPlatformEnum::LINUX]);
        expect($server->platform)->toBeInstanceOf(OsqueryPlatformEnum::class);
    });

    test('fillable attributes', function () {
        $data = [
            'name' => 'Test Server',
            'version' => '1.0',
            'ip_address' => '192.168.1.1',
            'ssh_port' => 22,
            'ssh_username' => 'admin',
            'platform' => OsqueryPlatformEnum::LINUX,
        ];

        $server = YnhServer::create($data);
        expect($server->name)->toBe('Test Server');
        expect($server->version)->toBe('1.0');
    });

});
