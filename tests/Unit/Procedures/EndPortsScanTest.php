<?php

use App\Events\BeginVulnsScan;
use App\Events\EndPortsScan;
use App\Jobs\TriggerScan;
use App\Models\Scan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

it('dispatches one BeginVulnsScan event per port found', function () {

    // Arrange
    Carbon::setTestNow(Carbon::now()->roundSeconds());
    asTenant1User();
    $assetAddress = 'www.example.com';
    $asset = createAsset($assetAddress, true);
    $taskId = '6409ae68ed42e11e31e5f19d';
    expect()->startPortsScanToBeCalled($assetAddress, $taskId);
    Event::fake([BeginVulnsScan::class]);
    app()->call([new TriggerScan, 'handle']);
    $scan = Scan::first();

    // Act
    expect()->getPortScanStatusToBeCalled($taskId, 'SUCCESS');
    expect()->getPortScanResultToBeCalled($taskId, [
        [
            'hostname' => $assetAddress,
            'ip' => '93.184.215.14',
            'port' => 443,
            'protocol' => 'tcp',
        ], [
            'hostname' => $assetAddress,
            'ip' => '93.184.215.14',
            'port' => 80,
            'protocol' => 'tcp',
        ],
    ]);
    event(new EndPortsScan(Carbon::now(), $asset, $scan, []));
    $scan->refresh();

    // Assert
    Event::assertDispatched(BeginVulnsScan::class, 2);
    // it stores ports scan end date
    $this->assertEquals($scan->ports_scan_ends_at, Carbon::now());
    // it stores ports found
    $this->assertDatabaseCount('am_ports', 2);
    $this->assertDatabaseHas('am_ports', [
        'scan_id' => $scan->id,
        'hostname' => $assetAddress,
        'ip' => '93.184.215.14',
        'port' => 443,
        'protocol' => 'tcp',
    ]);
    $this->assertDatabaseHas('am_ports', [
        // 'scan_id' => $scan->id,
        'hostname' => $assetAddress,
        'ip' => '93.184.215.14',
        'port' => 80,
        'protocol' => 'tcp',
    ]);
    // The 'scan_id' of the 2 ports are not the same because we
    // create a new Scan by copying the existing one for each discovered port
    // Why?

});

it('dispatches one BeginVulnsScan event even if no ports found', function () {

    // Arrange
    Carbon::setTestNow(Carbon::now()->roundSeconds());
    asTenant1User();
    $assetAddress = 'www.example.com';
    $asset = createAsset($assetAddress, true);
    $taskId = '6409ae68ed42e11e31e5f19d';
    expect()->startPortsScanToBeCalled($assetAddress, $taskId);
    Event::fake([BeginVulnsScan::class]);
    app()->call([new TriggerScan, 'handle']);
    $scan = Scan::first();

    // Act
    expect()->getPortScanStatusToBeCalled($taskId, 'SUCCESS');
    expect()->getPortScanResultToBeCalled($taskId, []);
    event(new EndPortsScan(Carbon::now(), $asset, $scan, []));
    $scan->refresh();

    // Assert
    Event::assertDispatched(BeginVulnsScan::class, 1);
    // it stores ports scan end date
    $this->assertEquals($scan->ports_scan_ends_at, Carbon::now());
    // it should create a dummy port
    $this->assertDatabaseHas('am_ports', [
        'scan_id' => $scan->id,
        'hostname' => 'localhost',
        'ip' => '127.0.0.1',
        'port' => 666,
        'protocol' => 'tcp',
    ]);

});

it('drops too old ports scan', function () {

    // Arrange
    asTenant1User();
    $assetAddress = 'www.example.com';
    $asset = createAsset($assetAddress, true);
    $taskId = '6409ae68ed42e11e31e5f19d';
    expect()->startPortsScanToBeCalled($assetAddress, $taskId);
    Event::fake([BeginVulnsScan::class]);
    app()->call([new TriggerScan, 'handle']);
    $scan = Scan::first();
    config(['towerify.adversarymeter.drop_scan_events_after_x_minutes' => 60]);

    // Act
    $this->travel(59)->minutes();
    event(new EndPortsScan(Carbon::now(), $asset, $scan, []));

    // Assert
    Event::assertNotDispatched(BeginVulnsScan::class);
    $this->assertDatabaseEmpty('am_ports');

});

it('drops ports scan if it is already ended', function () {

    // Arrange
    asTenant1User();
    $assetAddress = 'www.example.com';
    $asset = createAsset($assetAddress, true);
    $taskId = '6409ae68ed42e11e31e5f19d';
    expect()->startPortsScanToBeCalled($assetAddress, $taskId);
    Event::fake([BeginVulnsScan::class]);
    app()->call([new TriggerScan, 'handle']);
    $scan = Scan::first();
    $scan->ports_scan_ends_at = Carbon::now()->subHour();
    $scan->save();

    // Act
    event(new EndPortsScan(Carbon::now(), $asset, $scan, []));

    // Assert
    Event::assertNotDispatched(BeginVulnsScan::class);
    $this->assertDatabaseEmpty('am_ports');

});

it('drops ports scan if scan task has errors', function () {

    // Arrange
    asTenant1User();
    $assetAddress = 'www.example.com';
    $asset = createAsset($assetAddress, true);
    $taskId = '6409ae68ed42e11e31e5f19d';
    expect()->startPortsScanToBeCalled($assetAddress, $taskId);
    Event::fake([BeginVulnsScan::class]);
    app()->call([new TriggerScan, 'handle']);
    $scan = Scan::first();

    // Act
    expect()->getPortScanStatusToBeCalled($taskId, 'ERROR');
    event(new EndPortsScan(Carbon::now(), $asset, $scan, []));

    // Assert
    Event::assertNotDispatched(BeginVulnsScan::class);
    $this->assertDatabaseEmpty('am_ports');

});

it('recreates ports scan event if scan task is not terminated', function (string $returnedTaskStatus) {

    // Arrange
    Carbon::setTestNow(Carbon::now()->roundSeconds());
    asTenant1User();
    $assetAddress = 'www.example.com';
    $asset = createAsset($assetAddress, true);
    $taskId = '6409ae68ed42e11e31e5f19d';
    expect()->startPortsScanToBeCalled($assetAddress, $taskId);
    Event::fake([BeginVulnsScan::class]);
    app()->call([new TriggerScan, 'handle']);
    $scan = Scan::first();

    // Act
    expect()->getPortScanStatusToBeCalled($taskId, $returnedTaskStatus);
    event(new EndPortsScan(Carbon::now(), $asset, $scan, []));
    $scan->refresh();

    // Assert
    Event::assertNotDispatched(BeginVulnsScan::class);
    $this->assertDatabaseEmpty('am_ports');

})->with([
    'task status STARTED' => ['STARTED'],
    'task status PENDING' => ['PENDING'],
]);

it('stores country', function () {

    // Arrange
    asTenant1User();
    $assetAddress = 'www.example.com';
    $asset = createAsset($assetAddress, true);
    $taskId = '6409ae68ed42e11e31e5f19d';
    expect()->startPortsScanToBeCalled($assetAddress, $taskId);
    Event::fake([BeginVulnsScan::class]);
    app()->call([new TriggerScan, 'handle']);
    $scan = Scan::first();

    // Act
    $ipAddress = '93.184.215.14';
    expect()->getPortScanStatusToBeCalled($taskId, 'SUCCESS');
    expect()->getPortScanResultToBeCalled($taskId, [
        [
            'hostname' => $assetAddress,
            'ip' => $ipAddress,
            'port' => 443,
            'protocol' => 'tcp',
        ],
    ]);
    expect()->getIpGeolocToBeCalled($ipAddress, 'FR');
    event(new EndPortsScan(Carbon::now(), $asset, $scan, []));
    $scan->refresh();

    // Assert
    Event::assertDispatched(BeginVulnsScan::class, 1);
    $this->assertDatabaseHas('am_ports', [
        'scan_id' => $scan->id,
        'hostname' => $assetAddress,
        'ip' => $ipAddress,
        'port' => 443,
        'protocol' => 'tcp',
        'country' => 'FR',
    ]);

});

it('stores hosting provider information', function () {

    // Arrange
    asTenant1User();
    $assetAddress = 'www.example.com';
    $asset = createAsset($assetAddress, true);
    $taskId = '6409ae68ed42e11e31e5f19d';
    expect()->startPortsScanToBeCalled($assetAddress, $taskId);
    Event::fake([BeginVulnsScan::class]);
    app()->call([new TriggerScan, 'handle']);
    $scan = Scan::first();

    // Act
    $ipAddress = '93.184.215.14';
    expect()->getPortScanStatusToBeCalled($taskId, 'SUCCESS');
    expect()->getPortScanResultToBeCalled($taskId, [
        [
            'hostname' => $assetAddress,
            'ip' => $ipAddress,
            'port' => 443,
            'protocol' => 'tcp',
        ],
    ]);
    expect()->getIpOwnerToBeCalled($ipAddress, [
        'asn_description' => 'EDGECAST, US',
        'asn_registry' => 'ripencc',
        'asn' => '15133',
        'asn_cidr' => '93.184.215.0/24',
        'asn_country_code' => 'US',
        'asn_date' => '2008-06-02',
    ]);
    event(new EndPortsScan(Carbon::now(), $asset, $scan, []));

    // Assert
    Event::assertDispatched(BeginVulnsScan::class, 1);
    $this->assertDatabaseHas('am_ports', [
        'scan_id' => $scan->id,
        'hostname' => $assetAddress,
        'ip' => $ipAddress,
        'port' => 443,
        'protocol' => 'tcp',
        'hosting_service_description' => 'EDGECAST, US',
        'hosting_service_registry' => 'ripencc',
        'hosting_service_asn' => '15133',
        'hosting_service_cidr' => '93.184.215.0/24',
        'hosting_service_country_code' => 'US',
        'hosting_service_date' => '2008-06-02',
    ]);

});
