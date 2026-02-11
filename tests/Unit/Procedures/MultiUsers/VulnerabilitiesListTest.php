<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);

use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\Scan;

describe('vulnerabilities@list', function () {

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

    it('lists vulnerabilities without duplicates', function () {

        asTenant1User();

        $asset = Asset::factory()->monitored()->create();
        $scan = Scan::factory()->for($asset)->vulnsScanEnded()->create();
        $port = Port::factory()->for($scan)->create();

        // Many alerts with same uid on the same (ip, port, protocol)
        Alert::factory()
            ->for($port)
            ->assetMonitored()
            ->levelHigh()
            ->state(['uid' => 'dup-123'])
            ->create();

        Alert::factory()
            ->for($port)
            ->assetMonitored()
            ->levelHigh()
            ->state(['uid' => 'dup-123'])
            ->create();

        Alert::factory()
            ->for($port)
            ->assetMonitored()
            ->levelHigh()
            ->state(['uid' => 'dup-123'])
            ->create();

        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('vulnerabilities@list');

        $high = $response->json('result.high');

        expect($high)->toBeArray()->toHaveCount(1);
        expect(collect($high)->pluck('uid')->all())->toBe(['dup-123']);

        // More duplicates!
        Alert::factory()
            ->for($port)
            ->assetMonitored()
            ->levelHigh()
            ->state(['uid' => 'dup-456'])
            ->create();

        Alert::factory()
            ->for($port)
            ->assetMonitored()
            ->levelHigh()
            ->state(['uid' => 'dup-456'])
            ->create();

        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('vulnerabilities@list');

        $high = $response->json('result.high');

        expect($high)->toBeArray()->toHaveCount(2);
        expect(collect($high)->pluck('uid')->all())->toBe(['dup-123', 'dup-456']);

        // No duplicates!
        Alert::factory()
            ->for($port)
            ->assetMonitored()
            ->levelHigh()
            ->state(['uid' => 'nodup'])
            ->create();

        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('vulnerabilities@list');

        $high = $response->json('result.high');

        expect($high)->toBeArray()->toHaveCount(3);
        expect(collect($high)->pluck('uid')->all())->toBe(['dup-123', 'dup-456', 'nodup']);
    });

});