<?php

namespace Tests\Unit\Procedures\SingleUser;

use Tests\TestCaseWithDb;
use Sajya\Server\Testing\ProceduralRequests;

use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;

class AssetsDiscoverTest extends TestCaseWithDb
{
    use ProceduralRequests;

    public function testAssetsDiscover(): void
    {
        ApiUtils::shouldReceive('discover_public')
            ->once()
            ->with('example.com')
            ->andReturn([
                'subdomains' => ['www1.example.com', 'www1.example.com' /* duplicate! */, 'www2.example.com'],
            ]);

        $this->actingAs($this->userTenant1);

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@discover', [
                'domain' => 'example.com'
            ])
            ->assertJsonFragment([
                'result' => [
                    'subdomains' => ['www1.example.com', 'www1.example.com', 'www2.example.com'],
                ],
            ]);
    }

}
