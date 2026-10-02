<?php

namespace App\Console\Commands;

use App\Enums\DemoStagesEnum;
use App\Models\User;
use App\Services\DemoData;
use Illuminate\Console\Command;

/**
 * Put the demo account in a given state, to walk through the product:
 *
 *   php artisan demo:stage empty   → onboarding
 *   php artisan demo:stage scan    → assets + vulnerabilities + leaks
 *   php artisan demo:stage agent   → scan + server with agent
 */
class DemoStage extends Command
{
    protected $signature = 'demo:stage
                            {stage : empty, scan or agent}
                            {--email=demo@mydomain.com : Demo account (all its data is replaced)}';

    protected $description = 'Reset the demo account to a given stage (local and demo only)';

    private const array ALLOWED_ENVS = ['local', 'demo'];

    public function handle(DemoData $demo): int
    {
        // Wipes the account data: never in production
        if (!app()->environment(self::ALLOWED_ENVS)) {
            $this->error('Allowed environments: ' . implode(', ', self::ALLOWED_ENVS) . '.');
            return self::FAILURE;
        }

        $stage = DemoStagesEnum::tryFrom($this->argument('stage'));

        if (!$stage) {
            $this->error('Unknown stage. Use: empty, scan or agent.');
            return self::FAILURE;
        }

        $user = User::where('email', $this->option('email'))->first();

        if (!$user) {
            $this->error('Unknown user. Run CywiseDemoSeeder first.');
            return self::FAILURE;
        }

        $demo->stage($user, $stage);

        $this->info("Demo account {$user->email} set to stage '{$stage->value}'.");
        return self::SUCCESS;
    }
}
