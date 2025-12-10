<?php

use App\Jobs\TriggerScan;
use Illuminate\Support\Carbon;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

test('assets get after creation', function () {
    asTenant1User();
    $asset = createAsset('www.example.com', true);

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@get', [
            'asset' => 'www.example.com',
        ])
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'asset',
                'hiddenAlerts' => [],
                'modifications' => [
                    [
                        'asset_id',
                        'asset_name',
                        'timestamp',
                        'user',
                    ],
                ],
                'ports' => [],
                'tags' => [],
                'timeline' => [
                    'nb_vulns_scans_completed',
                    'nb_vulns_scans_running',
                    'next_scan',
                    'nmap' => [
                        'id',
                        'start',
                        'end',
                    ],
                    'sentinel' => [
                        'id',
                        'start',
                        'end',
                    ],
                ],
                'vulnerabilities' => [],
            ],
        ])
        ->assertJsonFragment([
            'asset' => 'www.example.com',
            'hiddenAlerts' => [],
            'asset_id' => $asset->id,
            'asset_name' => $asset->asset,
            'user' => tenant1User()->email,
            'ports' => [],
            'tags' => [],
            'nb_vulns_scans_completed' => 0,
            'nb_vulns_scans_running' => 0,
            'nmap' => [
                'id' => null,
                'start' => null,
                'end' => null,
            ],
            'sentinel' => [
                'id' => null,
                'start' => null,
                'end' => null,
            ],
            'vulnerabilities' => [],
        ]);
});

test('assets get after trigger scan', function () {
    $this->freezeSecond(function (Carbon $time) {

        asTenant1User();
        createAsset('www.example.com', true);
        $taskId = '6409ae68ed42e11e31e5f19d';

        expect()->startPortsScanToBeCalled('www.example.com', $taskId);

        app()->call([new TriggerScan, 'handle']);

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@get', [
                'asset' => 'www.example.com',
            ])
            ->assertJsonFragment([
                'asset' => 'www.example.com',
                'hiddenAlerts' => [],
                'ports' => [],
                'tags' => [],
                'nb_vulns_scans_completed' => 0,
                'nb_vulns_scans_running' => 1,
                'nmap' => [
                    'id' => $taskId,
                    'start' => $time,
                    'end' => null,
                ],
                'sentinel' => [
                    'id' => null,
                    'start' => null,
                    'end' => null,
                ],
                'vulnerabilities' => [],
            ]);

    });
});
