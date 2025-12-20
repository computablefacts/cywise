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

class ScheduleTask extends AbstractAction
{
    protected function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "schedule_task",
                "description" => "
Schedule a task to run at a specific time and/or when a given condition is met. The task output will be sent as an email report.
Provide the action to perform followed by a cron expression, a condition and a task to perform, using the format: 'action:cron:condition:task'.
The action (always schedule) must come first, followed by a colon and then a cron expression, followed by a colon and then a condition to meet, followed by a colon and then a task to execute.
For example:
- if the request is 'préviens-moi si www.example.com devient vulnérable', the input should be 'schedule:* * * * *:le site www.example.com est-il vulnérable ?:liste les vulnérabilités de www.example.com'
- if the request is 'envoie-moi un email tous les matins à 9h si www.example.com est vulnérable', the input should be 'schedule:0 9 * * *:le site www.example.com est-il vulnérable ?:liste les vulnérabilités de www.example.com'
- if the request is 'récapitule-moi tous les matins à 9h les vulnérabilités de www.example.com', the input should be 'schedule:0 9 * * *::liste les vulnérabilités de www.example.com'
- if the request is 'préviens-moi si John Doe se connecte au serveur 145.242.34.179', the input should be 'schedule:* * * * *:John Doe s'est-il connecté au serveur 145.242.34.179 ?:John Doe s'est connecté au serveur 145.242.34.179'
                ",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "input" => [
                            "type" => "string",
                            "description" => "The action to perform followed by a cron expression, a condition to meet and a task to perform, using the format: 'action:cron:condition:task'.",
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
        $cron = Str::trim(Str::before(Str::between($input, ':', ':'), ':'));
        $trigger = Str::trim(Str::afterLast(Str::between($input, ':', ':'), ':'));
        $task = Str::trim(Str::afterLast($input, ':'));

        if ($action !== 'schedule') {
            return new FailedAnswer(__("Invalid action. Please use schedule."));
        }
        $request = new JsonRpcRequest([
            'cron' => $cron,
            'trigger' => $trigger,
            'task' => $task,
        ]);
        $request->setUserResolver(fn() => $user);
        $result = (new ScheduledTasksProcedure())->create($request);
        return new SuccessfulAnswer($result['msg'] ?? __("The task ':task' has been scheduled. The task output will be sent to :email.", ['task' => $task, 'email' => $user->email]));
    }
}
