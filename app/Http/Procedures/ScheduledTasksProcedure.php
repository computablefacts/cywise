<?php

namespace App\Http\Procedures;

use App\AgentSquad\Providers\LlmsProvider;
use App\Http\Requests\JsonRpcRequest;
use App\Models\ScheduledTask;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Support\Str;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class ScheduledTasksProcedure extends Procedure
{
    public static string $name = 'scheduled-tasks';

    #[RpcMethod(
        description: 'Create a new scheduled task.',
        params: [
            'cron' => 'Cron expression (MIN HOUR DOM MON DOW).',
            'trigger' => 'Optional condition that must evaluate to true to run the task.',
            'task' => 'The task/instruction to execute when the schedule/trigger matches.',
        ],
        result: [
            'msg' => 'Success message.',
            'task_id' => 'The id of the created scheduled task.'
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'cron' => 'required|string',
            'trigger' => 'nullable|string',
            'task' => 'required|string',
        ]);

        if (!CronExpression::isValidExpression($params['cron'])) {
            throw new \InvalidArgumentException(__('Invalid cron expression ":cron". Please provide a valid cron expression in the format: MIN HOUR DOM MON DOW.', ['cron' => $params['cron']]));
        }

        $answer = LlmsProvider::provide("
            Analyze the following task and determine if it attempts to create, schedule, or add other scheduled tasks.
            Answer only with YES or NO and nothing else.
            The task to analyse:\n\n{$params['task']}
        ");

        if (Str::contains($answer, ['oui', 'yes'], true)) {
            throw new \InvalidArgumentException(__('Scheduled tasks cannot create other scheduled tasks. Please modify your task to remove any task creation instructions.'));
        }

        $user = $request->user();
        $task = ScheduledTask::create([
            'name' => LlmsProvider::provide("Summarize the task in about 10 words :\n\n{$params['task']}"),
            'cron' => $params['cron'],
            'trigger' => $params['trigger'] ?? '',
            'task' => $params['task'],
            'prev_run_date' => null,
            'next_run_date' => Carbon::instance((new CronExpression($params['cron']))->getNextRunDate()),
            'created_by' => $user->id,
        ]);

        return [
            'msg' => __('The task ":task" has been scheduled. The task output will be sent to :email.', ['task' => $params['task'], 'email' => $user->email]),
            'task_id' => $task->id,
        ];
    }

    #[RpcMethod(
        description: 'Pause or resume a scheduled task.',
        params: [
            'task_id' => 'The scheduled task id.',
            'enabled' => 'Optional boolean. If omitted, the flag will be toggled.'
        ],
        result: [
            'msg' => 'Success message.',
        ]
    )]
    public function toggle(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'task_id' => 'required|integer|exists:cb_scheduled_tasks,id',
            'enabled' => 'nullable|boolean',
        ]);

        /** @var ScheduledTask $task */
        $task = ScheduledTask::findOrFail($params['task_id']);
        $task->enabled = $params['enabled'] ?? !$task->enabled;
        $task->save();

        return [
            'msg' => __('Scheduled task updated.'),
        ];
    }

    #[RpcMethod(
        description: 'Delete a scheduled task.',
        params: [
            'task_id' => 'The scheduled task id.',
        ],
        result: [
            'msg' => 'Success message.'
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'task_id' => 'required|integer|exists:cb_scheduled_tasks,id',
        ]);
        ScheduledTask::findOrFail($params['task_id'])->delete();
        return [
            'msg' => __('Scheduled task deleted.'),
        ];
    }
}
