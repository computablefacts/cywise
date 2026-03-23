<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);

use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\Scan;
use Illuminate\Support\Facades\Storage;

it('creates a vulnerabilities report', function () {
    
    asTenant1User();

    Storage::fake('files-s3');
    $asset = Asset::factory()->monitored()->create();
    $scan = Scan::factory()->for($asset)->vulnsScanEnded()->create();
    $asset->cur_scan_id = $scan->ports_scan_id;
    $asset->save();
    $asset->refresh();
    $port = Port::factory()->for($scan)->create();
    Alert::factory()->for($port)->assetMonitored()->levelHigh()->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('reporting@create', ['report' => 'vulnerabilities']);

    $response->assertJsonStructure([
        'id',
        'jsonrpc',
        'result' => [
            'report',
        ],
    ]);

    $reportUrl = $response->json('result.report');
    expect($reportUrl)->toContain('/files/download/vulns-report-');
});

it('creates a ports report', function () {

    asTenant1User();

    Storage::fake('files-s3');
    $asset = Asset::factory()->monitored()->create();
    $scan = Scan::factory()->for($asset)->portsScanEnded()->create();
    $asset->cur_scan_id = $scan->ports_scan_id;
    $asset->save();
    $asset->refresh();
    Port::factory()->for($scan)->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('reporting@create', ['report' => 'ports']);

    $response->assertJsonStructure([
        'id',
        'jsonrpc',
        'result' => [
            'report',
        ],
    ]);

    $reportUrl = $response->json('result.report');
    expect($reportUrl)->toContain('/files/download/ports-report-');
});

it('creates an assets report', function () {

    asTenant1User();

    Storage::fake('files-s3');
    Asset::factory()->monitored()->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('reporting@create', ['report' => 'assets']);

    $response->assertJsonStructure([
        'id',
        'jsonrpc',
        'result' => [
            'report',
        ],
    ]);

    $reportUrl = $response->json('result.report');
    expect($reportUrl)->toContain('/files/download/assets-report-');
});
