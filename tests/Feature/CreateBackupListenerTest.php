<?php

namespace Tests\Feature;

use App\Events\CreateBackup;
use App\Helpers\SshConnection2;
use App\Listeners\CreateBackupListener;
use App\Models\User;
use App\Models\YnhBackup;
use App\Models\YnhServer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;

test('listener handles invalid event type', function () {

    Log::shouldReceive('error')
        ->once()
        ->with(Mockery::on(function ($arg) {
            return $arg instanceof \Exception
                && $arg->getMessage() === 'Invalid event type!';
        }));

    $listener = new CreateBackupListener;
    $listener->handle(new \stdClass);

});

test('listener skips backup when server is not ready', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->create(['is_ready' => false]);

    $event = new CreateBackup(Str::random(10), $user, $server);
    $listener = new CreateBackupListener;

    $listener->handle($event);

    expect(YnhBackup::count())->toBe(0);
});

test('listener creates backup successfully', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->create();

    $mockSsh = Mockery::mock(SshConnection2::class)->makePartial();
    $mockSsh->shouldReceive('download')->andReturn(true);

    $serverMock = Mockery::mock($server);
    $serverMock->shouldReceive('isReady')->andReturn(true);
    $serverMock->shouldReceive('sshConnection')->andReturn($mockSsh);
    $serverMock->shouldReceive('sshCreateBackup')->andReturn([
        'name' => 'backup_test',
        'size' => 1024,
        'results' => 'success',
    ]);

    Storage::fake('backups');

    $event = new CreateBackup(Str::random(10), $user, $serverMock);
    $listener = new CreateBackupListener;
    $listener->handle($event);

    expect(YnhBackup::count())->toBe(1);
    expect(YnhBackup::first()->name)->toBe('backup_test');
});

test('listener logs error when download fails', function () {

    $user = User::factory()->create();
    $server = YnhServer::factory()->create();

    $mockSsh = Mockery::mock(SshConnection2::class);
    $mockSsh->shouldReceive('download')->andReturn(false);

    $serverMock = Mockery::mock($server);
    $serverMock->shouldReceive('isReady')->andReturn(true);
    $serverMock->shouldReceive('sshConnection')->andReturn($mockSsh);
    $serverMock->shouldReceive('sshCreateBackup')->andReturn([
        'name' => 'backup_test',
        'size' => 1024,
        'results' => 'success',
    ]);

    Log::shouldReceive('debug')->once(); // User::actAs()
    Log::shouldReceive('error')
        ->once()
        ->with(Mockery::on(function ($arg) {
            return Str::contains($arg, '[BACKUP] Downloading file from')
                && Str::contains($arg, 'failed!');
        }));

    $event = new CreateBackup(Str::random(10), $user, $serverMock);
    $listener = new CreateBackupListener;
    $listener->handle($event);
});
