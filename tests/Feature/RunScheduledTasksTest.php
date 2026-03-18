<?php

namespace Tests\Feature;

use App\Http\Procedures\CyberBuddyProcedure;
use App\Jobs\RunScheduledTasks;
use App\Models\ScheduledTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCaseWithDb;

class RunScheduledTasksTest extends TestCaseWithDb
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_run_once_task_is_deleted_after_execution()
    {
        $user = User::factory()->create();

        // Mock LlmsProvider
        $this->mockLlmsProvider('YES', 'Summary');

        // Mock CyberBuddyProcedure
        $this->instance(
            CyberBuddyProcedure::class,
            Mockery::mock(CyberBuddyProcedure::class, function (MockInterface $mock) {
                $mock->shouldReceive('ask')->andReturn(['html' => 'Task result']);
            })
        );

        $task = ScheduledTask::create([
            'name' => 'Test Run Once Task',
            'cron' => '* * * * *',
            'trigger' => 'Should I run?',
            'task' => 'Run me once',
            'run_once' => true,
            'enabled' => true,
            'next_run_date' => Carbon::now()->subMinute(),
            'created_by' => $user->id,
        ]);

        (new RunScheduledTasks())->handle();

        $this->assertDatabaseMissing('cb_scheduled_tasks', ['id' => $task->id]);
    }

    public function test_regular_task_is_not_deleted_after_execution()
    {
        $user = User::factory()->create();

        $this->mockLlmsProvider('YES', 'Summary');

        $this->instance(
            CyberBuddyProcedure::class,
            Mockery::mock(CyberBuddyProcedure::class, function (MockInterface $mock) {
                $mock->shouldReceive('ask')->andReturn(['html' => 'Task result']);
            })
        );

        $task = ScheduledTask::create([
            'name' => 'Test Regular Task',
            'cron' => '* * * * *',
            'trigger' => 'Should I run?',
            'task' => 'Run me always',
            'run_once' => false,
            'enabled' => true,
            'next_run_date' => Carbon::now()->subMinute(),
            'created_by' => $user->id,
        ]);

        (new RunScheduledTasks())->handle();

        $this->assertDatabaseHas('cb_scheduled_tasks', ['id' => $task->id]);
        $task->refresh();
        $this->assertNotNull($task->last_email_sent_at);
        $this->assertTrue($task->next_run_date->isFuture());
    }

    private function mockLlmsProvider($conditionResponse, $summaryResponse)
    {
        // LlmsProvider::provide is static, so we need to mock it carefully if it's possible
        // Since it's a static call in RunScheduledTasks, we might need to use Mockery::mockAlias or just rely on the fact that it will probably fail in testing env if not handled.
        // Wait, LlmsProvider uses Http facade, so we can mock Http.

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => $conditionResponse // First call for trigger
                        ]
                    ]
                ]
            ], 200),
        ]);

        // Actually, RunScheduledTasks calls LlmsProvider::provide twice.
        // 1. for condition
        // 2. for summary

        \Illuminate\Support\Facades\Http::fakeSequence()
            ->push(['choices' => [['message' => ['content' => $conditionResponse]]]])
            ->push(['choices' => [['message' => ['content' => $summaryResponse]]]]);
    }
}
