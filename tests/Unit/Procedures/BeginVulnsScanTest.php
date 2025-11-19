<?php

use App\Events\BeginVulnsScan;
use App\Events\EndPortsScan;
use App\Jobs\TriggerScan;
use App\Models\Port;
use App\Models\Scan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

it('starts a vuln scan', function () {

    // Arrange
    Carbon::setTestNow(Carbon::now()->roundSeconds());
    asTenant1User();
    $scan = createScan(null, [
        'task_id' => '6409ae68ed42e11e31e5f19d',
    ]);
    createTag($scan->asset, 'demo');
    Carbon::setTestNow(Carbon::now()->addMinutes(10));
    $port = createPort($scan, [
        'hostname' => 'www.example.com',
        'ip' => '93.184.215.14',
        'port' => 80,
        'protocol' => 'tcp',
    ]);
    Carbon::setTestNow(Carbon::now()->addMinutes(5));
    expect()->startVulnsScanToBeCalled('www.example.com', '93.184.215.14', 80, 'tcp', ['demo'], 'a9a5d877-abed-4a39-8b4a-8316d451730d');

    // Act
    event(new BeginVulnsScan($scan, $port));

    // Assert
    $this->assertDatabaseHas('am_scans', [
        'ports_scan_id' => '6409ae68ed42e11e31e5f19d',
        'vulns_scan_id' => 'a9a5d877-abed-4a39-8b4a-8316d451730d',
        'ports_scan_begins_at' => Carbon::now()->subMinutes(15),
        'ports_scan_ends_at' => Carbon::now()->subMinutes(5),
        'vulns_scan_begins_at' => Carbon::now(),
        'vulns_scan_ends_at' => null,
    ]);

});

it('drops ports scan if vuln scan not launched', function () {

    asTenant1User();
    $scan = createScan(null, [
        'task_id' => '6409ae68ed42e11e31e5f19d',
    ]);
    $port = createPort($scan, [
        'hostname' => 'www.example.com',
        'ip' => '93.184.215.14',
        'port' => 80,
        'protocol' => 'tcp',
    ]);
    expect()->startVulnsScanToBeCalled('www.example.com', '93.184.215.14', 80, 'tcp', [], null);
    Log::expects('error')->with(Mockery::pattern('/^Vulns scan cannot be started/'));

    // Act
    event(new BeginVulnsScan($scan, $port));

    // Assert
    $this->assertDatabaseEmpty('am_ports');
    $this->assertDatabaseEmpty('am_scans');

});

