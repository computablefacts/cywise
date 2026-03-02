<?php

namespace App\Http\Procedures;

use App\AgentSquad\Providers\LlmsProvider;
use App\Http\Requests\JsonRpcRequest;
use App\Models\ScheduledTask;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Support\Str;
use Sajya\Server\Procedure;

class ScheduledTasksProcedure extends Procedure
{
    public static string $name = 'scheduled-tasks';

    #[RpcMethod(
        description: 'List the scheduled tasks of the current user.',
        params: [],
        result: [
            'tasks' => 'A list of tasks.',
        ],
        ai_examples: [
            "if the request is 'list my scheduled tasks', the input should be {}",
        ],
        ai_result: "
            @php
                \$tasks = collect(\$result['tasks'] ?? [])->map(fn(array \$task) => (new \App\Models\ScheduledTask())->forceFill(\$task));
            @endphp
            @if(\$tasks->isEmpty())
                No scheduled tasks found.
            @else
                Below is the list of your scheduled tasks:
                @foreach(\$tasks as \$task)
                @if(empty(\$task->trigger))
                - {{ \$task->id }}. {{ \$task->name }}: {{ \$task->task }} ({{ \$task->readableCron() }}). The last email has been sent at {{ \$task->last_email_sent_at->utc()->format('Y-m-d H:i:s') }} UTC.
                @else
                @if(\$task->cron === '* * * * *')
                - {{ \$task->id }}. {{ \$task->name }}: {{ \$task->task }} when {{ \$task->trigger }}. The last email has been sent at {{ \$task->last_email_sent_at->utc()->format('Y-m-d H:i:s') }} UTC.
                @else
                - {{ \$task->id }}. {{ \$task->name }}: {{ \$task->task }} when {{ \$task->trigger }} ({{ \$task->readableCron() }}). The last email has been sent at {{ \$task->last_email_sent_at->utc()->format('Y-m-d H:i:s') }} UTC.
                @endif
                @endif
                @endforeach
            @endif
        ",
    )]
    public function list(JsonRpcRequest $request): array
    {
        return [
            'tasks' => ScheduledTask::query()
                ->where('created_by', $request->user()->id)
                ->get(),
        ];
    }

    #[RpcMethod(
        description: 'Create a new scheduled task.',
        params: [
            'cron' => 'Cron expression MIN HOUR DOM MON DOW. (string|required)',
            'trigger' => 'Optional condition that must evaluate to true to run the task. (string|nullable)',
            'task' => 'The task/instruction to execute when the schedule/trigger matches. (string|required)',
            'run_once' => 'Optional boolean. If true, the task will be deleted after being successfully executed once. (boolean|nullable)',
        ],
        result: [
            'msg' => 'Success message.',
            'task_id' => 'The id of the created scheduled task.'
        ],
        ai_examples: [
            "if the request is 'préviens-moi si www.example.com devient vulnérable', the input should be '{\"cron\":\"* * * * *\",\"trigger\":\"le site www.example.com est-il vulnérable ?\",\"task\":\"liste les vulnérabilités de www.example.com\"}'",
            "if the request is 'envoie-moi un email tous les matins à 9h si www.example.com est vulnérable', the input should be '{\"cron\":\"0 9 * * *\",\"trigger\":\"le site www.example.com est-il vulnérable ?\",\"task\":\"liste les vulnérabilités de www.example.com\"}'",
            "if the request is 'récapitule-moi tous les matins à 9h les vulnérabilités de www.example.com', the input should be '{\"cron\":\"0 9 * * *\",\"trigger\":null,\"task\":\"liste les vulnérabilités de www.example.com\"}'",
            "if the request is 'préviens-moi si John Doe se connecte au serveur 145.242.34.179', the input should be '{\"cron\":\"* * * * *\",\"trigger\":\"John Doe s'est-il connecté au serveur 145.242.34.179 ?\",\"task\":\"John Doe s'est connecté au serveur 145.242.34.179\"}'"
        ],
        ai_result: "@json(\$result['msg'])",
    )]
    public function create(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'cron' => 'string|required',
            'trigger' => 'string|nullable',
            'task' => 'string|required',
            'run_once' => 'boolean|nullable',
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
            'run_once' => $params['run_once'] ?? false,
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
            'task_id' => 'The scheduled task id. (integer|required|exists:cb_scheduled_tasks,id)',
            'enabled' => 'Optional boolean. If omitted, the flag will be toggled. (boolean|nullable)'
        ],
        result: [
            'msg' => 'Success message.',
        ],
        ai_examples: [
            "if the request is 'arrête la tâche 1234', the input should be '{\"task_id\":6789,\"enabled\":false}'",
            "if the request is 'relance la tâche 456', the input should be '{\"task_id\":456,\"enabled\":true}'",
            "if the request is 'stop la tâche 6789', the input should be '{\"task_id\":6789,\"enabled\":false}'",
            "if the request is 'redémarre la tâche 19', the input should be '{\"task_id\":19,\"enabled\":true}'",
        ],
        ai_result: "@json(\$result['msg'])",
    )]
    public function toggle(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'task_id' => 'integer|required|exists:cb_scheduled_tasks,id',
            'enabled' => 'boolean|nullable',
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
            'task_id' => 'The scheduled task id. (integer|required|exists:cb_scheduled_tasks,id)',
        ],
        result: [
            'msg' => 'Success message.'
        ],
        ai_examples: [
            "if the request is 'supprime la tâche 1234', the input should be '{\"task_id\":6789}'",
        ],
        ai_result: "@json(\$result['msg'])",
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'task_id' => 'integer|required|exists:cb_scheduled_tasks,id',
        ]);
        ScheduledTask::findOrFail($params['task_id'])->delete();
        return [
            'msg' => __('Scheduled task deleted.'),
        ];
    }
}
