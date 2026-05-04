<?php

namespace Tests\Feature\AgentSquad\Assistants;

use App\AgentSquad\Assistants\TextAssistant;
use App\Models\Tenant;
use App\Models\Trace;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCaseWithDb;

class TextAssistantTest extends TestCaseWithDb
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

        Auth::login($this->user);
    }

    public function test_text_assistant_returns_text_from_deepinfra()
    {
        $assistant = TextAssistant::use()
            ->withRawPrompt('Hello Junie, respond with "Hi Junie!"')
            ->withThreadId('test-thread-123');

        $result = $assistant->text();

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Hi Junie!', $result);

        // Vérifier qu'une trace a été créée
        $this->assertDatabaseHas('cb_traces', [
            'thread_id' => 'test-thread-123',
            'created_by' => $this->user->id,
        ]);

        $trace = Trace::where('thread_id', 'test-thread-123')->first();

        $this->assertNotNull($trace);
        $this->assertStringContainsString('Junie', $trace->input);
        $this->assertStringContainsString($result, $trace->output);
    }

    public function test_text_assistant_structured_returns_object()
    {
        $assistant = TextAssistant::use()
            ->withRawPrompt('Respond with only valid JSON: {"answer": "Hello world!"}');

        $result = $assistant->structured();

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('parsed', $result);
        $this->assertStringContainsStringIgnoringCase('{"answer": "Hello world!"}', $result->raw);
        $this->assertEquals('Hello world!', $result->parsed['answer']);
    }
}
