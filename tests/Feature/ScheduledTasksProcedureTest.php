<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDb;

class ScheduledTasksProcedureTest extends TestCaseWithDb
{
    public function test_create_run_once_task()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => 'NO']]]], 200),
        ]);

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'scheduled-tasks@create',
            'params' => [
                'cron' => '0 0 * * *',
                'task' => 'Test task',
                'run_once' => true,
            ],
            'id' => 1,
        ];

        $response = $this->postJson('/api/v2/private/endpoint', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cb_scheduled_tasks', [
            'task' => 'Test task',
            'cron' => '0 0 * * *',
            'run_once' => true,
            'created_by' => $user->id,
        ]);
    }

    public function test_create_regular_task_defaults_to_false()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => 'NO']]]], 200),
        ]);

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'scheduled-tasks@create',
            'params' => [
                'cron' => '0 0 * * *',
                'task' => 'Test task regular',
            ],
            'id' => 1,
        ];

        $response = $this->postJson('/api/v2/private/endpoint', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cb_scheduled_tasks', [
            'task' => 'Test task regular',
            'cron' => '0 0 * * *',
            'run_once' => false,
            'created_by' => $user->id,
        ]);
    }

    public function test_create_task_with_natural_language_schedule()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Mock LLM responses:
        // 1. provide for task check (NO)
        // 2. provide for task summary
        Http::fake([
            '*' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => 'NO']]]])
                ->push(['choices' => [['message' => ['content' => 'Task Summary']]]]),
        ]);

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'scheduled-tasks@create',
            'params' => [
                'schedule' => '+10 minutes',
                'task' => 'Check the logs',
            ],
            'id' => 1,
        ];

        $response = $this->postJson('/api/v2/private/endpoint', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cb_scheduled_tasks', [
            'task' => 'Check the logs',
            'cron' => '* * * * *',
            'run_once' => true,
            'created_by' => $user->id,
        ]);
    }
}
