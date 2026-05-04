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

it('creates a remediation report from an alert id', function () {

    asTenant1User();

    Storage::fake('files-s3');
    $asset = Asset::factory()->monitored()->create([
        'asset' => 'example.com',
    ]);
    $scan = Scan::factory()->for($asset)->vulnsScanEnded()->create();
    $asset->cur_scan_id = $scan->ports_scan_id;
    $asset->save();
    $asset->refresh();
    $port = Port::factory()->for($scan)->https()->create();
    $alert = Alert::factory()->for($port)->levelHigh()->create([
        'vulnerability' => 'Exposed admin.php panel',
        'remediation' => 'Restrict access to the admin panel.',
        'ai_remediation' => 'Add authentication and IP allowlisting before exposing admin.php.',
    ]);

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('reporting@create', [
            'report' => 'remediation',
            'alert_id' => $alert->id,
        ]);

    $response->assertJsonStructure([
        'id',
        'jsonrpc',
        'result' => [
            'report',
        ],
    ]);

    $reportUrl = $response->json('result.report');
    expect($reportUrl)->toContain('/files/download/remediation-report-');
    expect($reportUrl)->toEndWith('.docx');
});

it('creates a remediation report from vulnerability and asset names', function () {

    asTenant1User();

    Storage::fake('files-s3');
    $asset = Asset::factory()->monitored()->create([
        'asset' => 'server.example.com',
    ]);
    $scan = Scan::factory()->for($asset)->vulnsScanEnded()->create();
    $asset->cur_scan_id = $scan->ports_scan_id;
    $asset->save();
    $asset->refresh();
    $port = Port::factory()->for($scan)->https()->create();
    Alert::factory()->for($port)->levelHigh()->create([
        'vulnerability' => 'Exposed uploads directory',
        'remediation' => 'Disable directory listing.',
        'ai_remediation' => 'Block direct listing of /uploads and serve files through controlled routes.',
    ]);

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('reporting@create', [
            'report' => 'remediation',
            'vulnerability_name' => 'uploads',
            'asset_name' => 'server.example.com',
        ]);

    $response->assertJsonStructure([
        'id',
        'jsonrpc',
        'result' => [
            'report',
        ],
    ]);

    $reportUrl = $response->json('result.report');
    expect($reportUrl)->toContain('/files/download/remediation-report-');
    expect($reportUrl)->toEndWith('.docx');
});
