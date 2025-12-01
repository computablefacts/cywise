<?php

use App\Models\Port;
use App\Models\Scan;

it('creates a port with default attributes', function () {
    $port = Port::factory()->create();

    expect($port)->toBeInstanceOf(Port::class)
        ->and($port->scan_id)->not->toBeNull()
        ->and($port->hostname)->not->toBeNull()
        ->and($port->ip)->not->toBeNull()
        ->and($port->port)->toBeBetween(1, 65535)
        ->and($port->protocol)->toBeIn(['tcp', 'udp']);
});

it('creates a port with a specific scan', function () {
    $scan = Scan::factory()->create();
    $port = Port::factory()->create(['scan_id' => $scan->id]);

    expect($port->scan_id)->toBe($scan->id);
});

it('creates a port with custom hostname', function () {
    $hostname = 'example.com';
    $port = Port::factory()->create(['hostname' => $hostname]);

    expect($port->hostname)->toBe($hostname);
});

it('creates a port with custom ip', function () {
    $ip = '192.168.1.1';
    $port = Port::factory()->create(['ip' => $ip]);

    expect($port->ip)->toBe($ip);
});

it('creates a port with custom port number', function () {
    $portNumber = 8080;
    $port = Port::factory()->create(['port' => $portNumber]);

    expect($port->port)->toBe($portNumber);
});

it('creates a port with tcp protocol', function () {
    $port = Port::factory()->create(['protocol' => 'tcp']);

    expect($port->protocol)->toBe('tcp');
});

it('creates multiple ports', function () {
    $ports = Port::factory()->count(3)->create();

    expect($ports)->toHaveCount(3)
        ->each->toBeInstanceOf(Port::class);
});

it('creates an http port', function () {
    $port = Port::factory()->http()->create();

    expect($port->port)->toBe(80)
        ->and($port->protocol)->toBe('tcp')
        ->and($port->ssl)->toBe(false);
});

it('creates an https port', function () {
    $port = Port::factory()->https()->create();

    expect($port->port)->toBe(443)
        ->and($port->protocol)->toBe('tcp')
        ->and($port->ssl)->toBe(true);
});
