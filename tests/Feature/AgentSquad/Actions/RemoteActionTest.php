<?php

namespace Tests\Feature\AgentSquad\Actions;

use App\AgentSquad\Actions\RemoteAction;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\Models\RemoteAction as RemoteActionModel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDb;

class RemoteActionTest extends TestCaseWithDb
{
    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Test Tenant']);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Teste que les appels vers une URL externe utilisent le client HTTP (réseau).
     */
    public function test_remote_action_calls_external_api_via_http()
    {
        Http::fake([
            'https://external-api.com/*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['message' => 'Hello world!']
            ], 200)
        ]);

        $model = new RemoteActionModel([
            'name' => 'external_call',
            'description' => 'Calls an external API',
            'url' => 'https://external-api.com/rpc',
            'payload_template' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'hello',
                'params' => []
            ],
            'response_template' => 'Response: {{ $result["message"] }}',
            'schema' => [],
        ]);

        $action = new RemoteAction($model);
        $answer = $action->execute($this->user, 'thread-123', [], '{}');

        $this->assertInstanceOf(SuccessfulAnswer::class, $answer);
        $this->assertEquals('Response: Hello world!', $answer->markdown());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://external-api.com/rpc' &&
                $request['method'] === 'hello';
        });
    }

    /**
     * Teste que les appels vers l'URL interne de Cywise utilisent app()->handle() (pas de réseau).
     */
    public function test_remote_action_calls_internal_cywise_api_via_app_handle()
    {
        // On s'assure que Http::fake ne capture rien, ou du moins on vérifiera qu'il n'est pas appelé
        Http::fake();

        $model = new RemoteActionModel([
            'name' => 'internal_call',
            'description' => 'Calls Cywise internal API',
            'url' => '/api/v2/private/endpoint',
            'payload_template' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'assets@list',
                'params' => []
            ],
            'response_template' => 'Response: {{ json_encode($result) }}',
            'schema' => [],
        ]);

        $action = new RemoteAction($model);
        $answer = $action->execute($this->user, 'thread-456', [], '{}');

        $this->assertInstanceOf(SuccessfulAnswer::class, $answer);
        // On vérifie que c'est bien la réponse d'AssetsProcedure (ici vide)
        $this->assertStringContainsString('{"assets":[]}', html_entity_decode($answer->markdown()));

        // Vérification qu'aucun appel HTTP externe n'a été fait
        Http::assertNothingSent();
    }

    /**
     * Teste que les erreurs JSON-RPC retournent un SuccessfulAnswer avec le message d'erreur.
     */
    public function test_remote_action_returns_successful_answer_on_jsonrpc_error()
    {
        Http::fake([
            'https://external-api.com/*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'error' => [
                    'code' => -32601,
                    'message' => 'Method not found'
                ]
            ], 200)
        ]);

        $model = new RemoteActionModel([
            'name' => 'error_call',
            'description' => 'Calls an external API that returns an error',
            'url' => 'https://external-api.com/rpc',
            'payload_template' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'unknown_method',
                'params' => []
            ],
            'response_template' => 'Response: {{ $result["message"] }}',
            'schema' => [],
        ]);

        $action = new RemoteAction($model);
        $answer = $action->execute($this->user, 'thread-789', [], '{}');

        $this->assertInstanceOf(SuccessfulAnswer::class, $answer);
        $this->assertEquals('Method not found', $answer->markdown());
    }
}
