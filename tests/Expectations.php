<?php

use App\Helpers\VulnerabilityScannerApiUtilsFacade as VulnerabilityScannerApiUtils;

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('startPortsScanToBeCalled', function ($assetAddress = 'www.example.com', $taskId = '6409ae68ed42e11e31e5f19d') {
    return VulnerabilityScannerApiUtils::shouldReceive('task_nmap_public')
        ->once()
        ->with($assetAddress)
        ->andReturn([
            'task_id' => $taskId,
        ]);
});

expect()->extend('startPortsScanToNotBeCalled', function () {
    return VulnerabilityScannerApiUtils::shouldReceive('task_nmap_public')
        ->never();
});

expect()->extend('getPortScanStatusToBeCalled', function ($taskId = '6409ae68ed42e11e31e5f19d', $taskStatus = 'SUCCESS') {
    return VulnerabilityScannerApiUtils::shouldReceive('task_status_public')
        ->once()
        ->with($taskId)
        ->andReturn([
            'task_status' => $taskStatus,
        ]);
});

expect()->extend('getPortScanResultToBeCalled', function ($taskId = '6409ae68ed42e11e31e5f19d', $taskResult = []) {
    return VulnerabilityScannerApiUtils::shouldReceive('task_result_public')
        ->once()
        ->with($taskId)
        ->andReturn([
            'task_result' => $taskResult,
        ]);
});

expect()->extend('getIpGeolocToBeCalled', function ($inputIp = '93.184.215.14', $outputCountryIsoCode = 'US') {
    return VulnerabilityScannerApiUtils::shouldReceive('ip_geoloc_public')
        ->once()
        ->with($inputIp)
        ->andReturn([
            'data' => [
                'country' => [
                    'iso_code' => $outputCountryIsoCode,
                ],
            ],
        ]);
});

expect()->extend('getIpOwnerToBeCalled', function ($inputIp = '93.184.215.14', $outputData = []) {
    return VulnerabilityScannerApiUtils::shouldReceive('ip_whois_public')
        ->once()
        ->with($inputIp)
        ->andReturn([
            'data' => $outputData,
        ]);
});

expect()->extend('startVulnsScanToBeCalled', function (
    $assetAddress = 'www.example.com',
    $inputIp = '93.184.215.14',
    $inputPort = 80,
    $inputProtocol = 'tcp',
    $inputTags = ['demo'],
    $outputScanId = 'a9a5d877-abed-4a39-8b4a-8316d451730d') {
    return VulnerabilityScannerApiUtils::shouldReceive('task_start_scan_public')
        ->once()
        ->with($assetAddress, $inputIp, $inputPort, $inputProtocol, $inputTags)
        ->andReturn([
            'scan_id' => $outputScanId,
        ]);
});
