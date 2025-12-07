<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\Models\ScheduledTask;
use App\Models\User;

class ListScheduledTasks extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "list_scheduled_tasks",
                "description" => "Display the list of scheduled tasks for the current user.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [],
                    "required" => [],
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
        $tasks = ScheduledTask::query()
            ->where('created_by', $user->id)
            ->get()
            ->map(fn(ScheduledTask $task) => "<b>{$task->id}.</b> {$task->name}")
            ->join("</li><li>");
        return empty($tasks) ?
            new SuccessfulAnswer(__("No scheduled tasks found.")) :
            new SuccessfulAnswer("<p>Scheduled tasks:</p><ul><li>{$tasks}</li></ul>", [], true);
    }
}
