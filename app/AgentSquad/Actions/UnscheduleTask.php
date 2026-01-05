<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\Http\Procedures\ScheduledTasksProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use Illuminate\Support\Str;

class UnscheduleTask extends AbstractAction
{
    protected function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "unschedule_task",
                "description" => "
Unschedule a task.
Provide the action to perform followed by a task identifier, using the format: 'action:task_id'.
The action (always unschedule) must come first, followed by a colon and then a task identifier.
For example:
- if the request is 'arrête la tâche 1234', the input should be 'unschedule:1234'
- if the request is 'stop la tâche 6789', the input should be 'unschedule:6789'
                ",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "input" => [
                            "type" => "string",
                            "description" => "The action to perform followed by a task identifier, using the format: 'action:task_id'.",
                        ],
                    ],
                    "required" => ["input"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct()
    {
        //
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        $action = Str::trim(Str::before($input, ':'));
        $taskId = Str::trim(Str::afterLast($input, ':'));

        if ($action !== 'unschedule') {
            return new FailedAnswer(__("Invalid action. Please use unschedule."));
        }
        if (!is_numeric($taskId)) {
            return new FailedAnswer(__("Invalid task identifier. Please provide a valid task identifier."));
        }
        $request = new JsonRpcRequest(['task_id' => $taskId]);
        $request->setUserResolver(fn() => $user);
        (new ScheduledTasksProcedure())->delete($request);
        return new SuccessfulAnswer(__("The task has been unscheduled successfully."));
    }
}
