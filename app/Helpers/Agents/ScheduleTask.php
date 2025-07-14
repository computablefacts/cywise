<?php

namespace App\Helpers\Agents;

use App\Models\ScheduledTask;
use App\Models\User;
use Cron\CronExpression;
use Illuminate\Support\Carbon;

/** @deprecated */
class ScheduleTask extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "schedule_task",
                "description" => "Schedule a task to run at a specific time. The task output will be sent as an email report.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "name" => [
                            "type" => "string",
                            "description" => "The task name or subject. If none is specified, try to infer one from the task itself. It will be used as the email subject."
                        ],
                        "cron" => [
                            "type" => "string",
                            "description" => "A cron expression that defines the schedule for the task. The expression should follow the standard cron format: \"MIN HOUR DOM MON DOW\", where:\n- MIN: Minutes (0-59)\n- HOUR: Hours (0-23)\n- DOM: Day of the month (1-31)\n- MON: Month (1-12)\n- DOW: Day of the week (0-6, where 0 is Sunday)",
                        ],
                        "task" => [
                            "type" => "string",
                            "description" => "The task to be scheduled. The task should not require any arguments. The task will be executed at the specified time and the task output will be sent as an email report.",
                        ],
                    ],
                    "required" => ["cron", "task"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct(User $user, string $threadId, array $messages, array $args = [])
    {
        parent::__construct($user, $threadId, $messages, $args);
    }

    function execute(): AbstractAction
    {
        $name = $this->args['name'] ?? 'Cywise - Votre rapport est prêt!';
        $cron = $this->args['cron'] ?? '';
        $task = $this->args['task'] ?? '';

        if (empty($cron) || !CronExpression::isValidExpression($cron)) {
            $this->output = "Invalid cron expression '{$cron}'. Please provide a valid cron expression in the format: MIN HOUR DOM MON DOW.  The task scheduling process has been stopped to prevent any issues. If you need further assistance, feel free to contact support.";
        } else if (empty($task)) {
            $this->output = "Invalid task '{$task}'. Please provide a valid task to be scheduled. The task scheduling process has been stopped to prevent any issues. If you need further assistance, feel free to contact support.";
        } else {
            $expr = new CronExpression($cron);
            ScheduledTask::create([
                'name' => $name,
                'cron' => $cron,
                'task' => $task,
                'prev_run_date' => null,
                'next_run_date' => Carbon::instance($expr->getNextRunDate()),
                'created_by' => $this->user->id,
            ]);
            $this->output = "The task '{$task}' has been scheduled. The task output will be sent to '{$this->user->email}'.";
        }
        return $this;
    }
}
