<?php

use App\Models\Alert;
use App\Models\Port;

it('creates an alert with default attributes', function () {
    $alert = Alert::factory()->create();

    expect($alert)->toBeInstanceOf(Alert::class)
        ->and($alert->port_id)->not->toBeNull()
        ->and($alert->type)->not->toBeNull()
        ->and($alert->level)->toBeIn(['Critical', 'High', 'Medium', 'Low'])
        ->and($alert->vulnerability)->not->toBeNull()
        ->and($alert->remediation)->not->toBeNull();
});

it('creates an alert with level High', function () {
    $alert = Alert::factory()->levelHigh()->create();

    expect($alert->level)->toBe('High');
});

it('creates an alert with level Medium', function () {
    $alert = Alert::factory()->levelMedium()->create();

    expect($alert->level)->toBe('Medium');
});

it('creates an alert with level Low', function () {
    $alert = Alert::factory()->levelLow()->create();

    expect($alert->level)->toBe('Low');
});

it('creates an alert associated with a port', function () {
    asTenant1User();
    $port = Port::factory()->create();
    $alert = Alert::factory()->create(['port_id' => $port->id]);

    expect($alert->port_id)->toBe($port->id)
        ->and($alert->port)->toBeInstanceOf(Port::class);
});

it('creates multiple alerts', function () {
    $alerts = Alert::factory()->count(5)->create();

    expect($alerts)->toHaveCount(5)
        ->each->toBeInstanceOf(Alert::class);
});

it('creates alerts with different levels', function () {
    $highAlert = Alert::factory()->levelHigh()->create();
    $mediumAlert = Alert::factory()->levelMedium()->create();
    $lowAlert = Alert::factory()->levelLow()->create();

    expect($highAlert->level)->toBe('High')
        ->and($mediumAlert->level)->toBe('Medium')
        ->and($lowAlert->level)->toBe('Low');
});

it('creates alert with a monitored asset', function () {
    $alert = Alert::factory()->assetMonitored()->create();

    expect($alert->asset()->is_monitored)->toBeTrue();
});

it('creates alert with an unmonitored asset', function () {
    $alert = Alert::factory()->assetUnmonitored()->create();

    expect($alert->asset()->is_monitored)->toBeFalse();
});
