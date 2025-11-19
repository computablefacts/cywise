<?php

use App\Jobs\TriggerScan;
use App\Models\Scan;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

it('should trigger a scan for an asset', function () {

    asTenant1User();
    $asset = createAsset('www.example.com', true);
    $taskId = '6409ae68ed42e11e31e5f19d';

    expect()->startPortsScanToBeCalled('www.example.com', $taskId);

    app()->call([new TriggerScan, 'handle']);

    // Vérifier que le scan a bien été créé dans la base de données
    $this->assertDatabaseCount('am_scans', 1);
    $this->assertDatabaseHas('am_scans', [
        'asset_id' => $asset->id,
        'ports_scan_id' => $taskId,
        'vulns_scan_id' => null,
    ]);
    $scan = Scan::first();
    $this->assertNotNull($scan->ports_scan_begins_at);
    $this->assertNull($scan->ports_scan_ends_at);
    $this->assertNull($scan->vulns_scan_id);
    $this->assertNull($scan->vulns_scan_begins_at);
    $this->assertNull($scan->vulns_scan_ends_at);

});

it('should NOT trigger a scan with no asset', function () {

    expect()->startPortsScanToNotBeCalled();

    app()->call([new TriggerScan, 'handle']);

    // Vérifier qu'aucun scan n'a été créé dans la base de données
    $this->assertDatabaseCount('am_scans', 0);

});

it('should NOT trigger a scan with an asset not monitored', function () {

    asTenant1User();
    createAsset('www.example.com', false);

    expect()->startPortsScanToNotBeCalled();

    app()->call([new TriggerScan, 'handle']);

    // Vérifier qu'aucun scan n'a été créé dans la base de données
    $this->assertDatabaseCount('am_scans', 0);

});

it('should remove dangling scans', function () {
    //
})->todo('Not yet implemented');
