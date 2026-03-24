<?php

namespace Tests\Unit;

use App\AgentSquad\Assistants\TextAssistant;
use App\Models\Trace;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDb;

class TextAssistantTraceTest extends TestCaseWithDb
{
    public function test_text_assistant_creates_trace()
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Hello response'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $assistant = new TextAssistant();
        $result = $assistant->withRawPrompt('Hello prompt')->text();

        $this->assertEquals('Hello response', $result);
        $this->assertDatabaseHas('cb_traces', [
            'input' => json_encode([['role' => 'user', 'content' => 'Hello prompt']]),
            'output' => json_encode([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Hello response'
                        ]
                    ]
                ]
            ]),
        ]);

        /** @var Trace $trace */
        $trace = Trace::first();
        $this->assertGreaterThan(0, $trace->elapsed_time_in_seconds);
    }
}
