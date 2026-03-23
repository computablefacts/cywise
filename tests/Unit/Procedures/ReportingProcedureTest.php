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
    Alert::factory()->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelHigh()->create();

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

    $xlsx = $response->json('result.xlsx');
    expect($xlsx)->toContain('/files/download/vulns-report-');
});

it('creates a ports report', function () {

    asTenant1User();

    Storage::fake('files-s3');
    Port::factory()->for(
        Scan::factory()->for(
            Asset::factory()->monitored()->create()
        )->portsScanEnded()->create()
    )->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('reporting@create', ['type' => 'report']);

    $response->assertJsonStructure([
        'id',
        'jsonrpc',
        'result' => [
            'report',
        ],
    ]);

    $xlsx = $response->json('result.xlsx');
    expect($xlsx)->toContain('/files/download/ports-report-');
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

    $xlsx = $response->json('result.xlsx');
    expect($xlsx)->toContain('/files/download/assets-report-');
});
