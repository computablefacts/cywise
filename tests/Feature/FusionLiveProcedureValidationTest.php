<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\TestCaseWithDb;

class FusionLiveProcedureValidationTest extends TestCaseWithDb
{
    public function test_workspaces_throws_exception_when_credentials_missing()
    {
        Config::set('towerify.hasher.nonce', 'azertyuiop1234567890');

        $user = User::factory()->create([
            'fusionlive_username' => null,
            'fusionlive_password' => null,
        ]);
        $this->actingAs($user);

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'fusionlive@workspaces',
            'params' => [],
            'id' => 1,
        ];

        $response = $this->postJson('/api/v2/private/endpoint', $payload);

        // Sajya handles exceptions and returns them in the JSON-RPC error format
        $response->assertStatus(200);
        $response->assertJsonPath('error.message', 'Missing FusionLive credentials.');
    }

    public function test_list_documents_throws_exception_when_credentials_missing()
    {
        Config::set('towerify.hasher.nonce', 'azertyuiop1234567890');

        $user = User::factory()->create([
            'fusionlive_username' => 'someuser',
            'fusionlive_password' => '', // empty string
        ]);
        $this->actingAs($user);

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'fusionlive@documents',
            'params' => [
                'workspace_id' => 123,
            ],
            'id' => 1,
        ];

        $response = $this->postJson('/api/v2/private/endpoint', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('error.message', 'Missing FusionLive credentials.');
    }
}
