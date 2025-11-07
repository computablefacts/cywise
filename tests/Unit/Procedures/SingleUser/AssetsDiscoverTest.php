<?php

use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

test('assets discover', function () {
    ApiUtils::shouldReceive('discover_public')
        ->once()
        ->with('example.com')
        ->andReturn([
            'subdomains' => ['www1.example.com', 'www1.example.com' /* duplicate! */, 'www2.example.com'],
        ]);

    asTenant1User();

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@discover', [
            'domain' => 'example.com',
        ])
        ->assertJsonFragment([
            'result' => [
                'subdomains' => ['www1.example.com', 'www1.example.com', 'www2.example.com'],
            ],
        ]);
});
