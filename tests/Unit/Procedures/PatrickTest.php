<?php

namespace Tests\Unit;

use Tests\TestCaseWithDb;
use Tests\TestCase;
use Sajya\Server\Testing\ProceduralRequests;

use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Asset;

class PatrickTest extends TestCase
{
    use ProceduralRequests;

    /**
     * A basic RPC test example.
     */
    public function testAssetsDiscover(): void
    {
        ApiUtils::shouldReceive('discover_public')
            ->once()
            ->with('example.com')
            ->andReturn([
                'subdomains' => ['www1.example.com', 'www1.example.com' /* duplicate! */, 'www2.example.com'],
            ]);

        // Authentifier un utilisateur
        $this->actingAs($this->user);

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

    /** 
     * Test assets@create suivi de assets@delete
     */
    public function testAssetsCreateAndDelete(): void
    {
        // Authentifier un utilisateur
        $this->actingAs($this->user);

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@create', [
                'asset' => 'www.example.com',
                'watch' => false,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'asset' => [
                        'asset',
                        'status',
                        'tags',
                        'tld',
                        'type',
                        'uid',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'asset' => 'www.example.com',
                'status' => 'invalid',
                'tags' => [],
                'tld' => 'example.com',
                'type' => 'DNS',
            ]);

        
        // Vérifier que l'actif a bien été créé dans la base de données
        $assets = Asset::where('asset', 'www.example.com')->get();
        $this->assertEquals(1, $assets->count());

        $assetId = $assets->first()->id;

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@delete', [
                'asset_id' => $assetId,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ])
            ->assertJsonFragment([
                'result' => [
                    'msg' => 'www.example.com has been removed.',
                ],
            ]);
    }
}
