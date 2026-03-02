<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Spatie\Health\Health;
use Spatie\Health\ResultStores\ResultStore;

class RunLevelHealthChecksCommand extends Command
{
    protected $signature = 'health:check-level {level : The check level to run (critical, medium, info)}';

    protected $description = 'Run health checks for a specific level and cache the results';

    public function handle(Health $health, ResultStore $resultStore): int
    {
        $level = $this->argument('level');

        $checks = $health->registeredChecks()
            ->filter(fn ($check) => str_starts_with($check->getName(), "{$level}."));

        if ($checks->isEmpty()) {
            $this->error("No checks found for level: {$level}");

            return self::FAILURE;
        }

        $this->info("Running {$level} health checks...");

        $results = $checks->map(function ($check) {
            try {
                $result = $check->run();
            } catch (Exception) {
                $result = $check->markAsCrashed();
            }

            return $result->check($check)->endedAt(now());
        });

        $cached = [
            'finishedAt' => now()->timestamp,
            'checkResults' => $results->map(fn ($result) => [
                'name' => $result->check->getName(),
                'label' => $result->check->getLabel(),
                'status' => $result->status->value,
                'notificationMessage' => $result->getNotificationMessage(),
                'shortSummary' => $result->getShortSummary(),
                'meta' => $result->meta,
            ])->values()->toArray(),
        ];

        cache()->put("health:level_results:{$level}", $cached, now()->addHours(2));

        // Also persist to the Spatie result store (DB) to keep history
        $health->resultStores()->each(fn (ResultStore $store) => $store->save($results));

        $this->info("Done. Results cached for level '{$level}'.");

        return self::SUCCESS;
    }
}
