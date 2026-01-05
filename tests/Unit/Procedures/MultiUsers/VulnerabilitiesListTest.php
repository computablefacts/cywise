<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\Scan;

it('lists vulnerabilities for current user only', function () {
    asTenant1User();
    Alert::factory(3)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelHigh()->create();

    asTenant2User();
    Alert::factory(1)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();

    asTenant1User();
    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('vulnerabilities@list');

    expect($response->json('result.high'))->toBeArray()->toHaveCount(3);
    expect($response->json('result.medium'))->toBeArray()->toHaveCount(0);
    expect($response->json('result.low'))->toBeArray()->toHaveCount(0);
});
