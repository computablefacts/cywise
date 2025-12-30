<?php

namespace App\AgentSquad;

use App\AgentSquad\Actions\CyberBuddy;
use App\AgentSquad\Actions\ListScheduledTasks;
use App\AgentSquad\Actions\ListVulnerabilities;
use App\AgentSquad\Actions\ScheduleTask;
use App\AgentSquad\Actions\ToggleUserGetsAuditReport;
use App\AgentSquad\Actions\UnscheduleTask;
use App\Models\ActionSetting;
use App\Models\User;

class ActionsRegistry
{
    /**
     * Returns a map of action name => AbstractAction
     */
    public static function all(): array
    {
        // Add new actions here to make them configurable in the UI
        $classes = [
            CyberBuddy::class,
            ListVulnerabilities::class,
            ToggleUserGetsAuditReport::class,
            ScheduleTask::class,
            UnscheduleTask::class,
            ListScheduledTasks::class,
        ];

        $map = [];

        foreach ($classes as $cls) {
            /** @var AbstractAction $inst */
            $inst = new $cls();
            $map[$inst->name()] = $inst;
        }

        $actions = \App\Models\RemoteAction::all();

        foreach ($actions as $action) {
            $inst = new \App\AgentSquad\Actions\RemoteAction($action);
            $map[$inst->name()] = $inst;
        }
        return $map;
    }

    /**
     * Resolve enabled action classes for a user, considering user overrides first, then tenant-level, default=true.
     * @return array<AbstractAction>
     */
    public static function enabledFor(User $user): array
    {
        $actions = self::all();
        $result = [];
        $tenantId = $user->tenant_id;

        // Fetch settings for user and tenant in one shot
        $settings = ActionSetting::query()
            ->where(function ($q) use ($user, $tenantId) {
                $q->where(function ($q2) use ($tenantId) {
                    $q2->where('scope_type', 'tenant')->where('scope_id', $tenantId);
                })->orWhere(function ($q3) use ($user) {
                    $q3->where('scope_type', 'user')->where('scope_id', $user->id);
                });
            })
            ->get()
            ->groupBy(fn($row) => "{$row->scope_type}#{$row->action}");

        foreach ($actions as $name => $action) {

            // user override
            $userKey = 'user#' . $name;
            $enabled = true; // default

            if (isset($settings[$userKey])) {
                $enabled = (bool)($settings[$userKey][0]->enabled ?? true);
            } else {
                $tenantKey = 'tenant#' . $name;
                if (isset($settings[$tenantKey])) {
                    $enabled = (bool)($settings[$tenantKey][0]->enabled ?? true);
                }
            }
            if ($enabled) {
                $result[] = $action;
            }
        }
        return $result;
    }
}
