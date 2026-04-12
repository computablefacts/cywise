<?php

use App\Console\Commands\RunLevelHealthChecksCommand;
use App\Jobs\Cleanup;
use App\Jobs\DeleteEmbeddedChunks;
use App\Jobs\DownloadDebianSecurityBugTracker;
use App\Jobs\EmbedChunks;
use App\Jobs\ProcessIncomingEmails;
use App\Jobs\RunScheduledTasks;
use App\Jobs\TriggerAssetsDiscovery;
use App\Jobs\TriggerScan;
use App\Jobs\TriggerSendAuditReport;
use App\Jobs\UpdateTables;
use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \Lab404\Impersonate\ImpersonateServiceProvider::class,
        \Wave\WaveServiceProvider::class,
        \DevDojo\Themes\ThemesServiceProvider::class,
        \Laravel\Scout\ScoutServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Global Middlewares (used for all requests)
        $middleware->trustProxies(
            [
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
            ],
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );
        $middleware->preventRequestsDuringMaintenance([]);
        $middleware->trimStrings([
            'current_password',
            'password',
            'password_confirmation',
        ]);
        $middleware->append(\Filament\Http\Middleware\DisableBladeIconComponents::class);

        // Group Middleware (used for some routes)
        $middleware->encryptCookies(except: [
            'theme',
        ]);
        $middleware->validateCsrfTokens(except: [
            '/webhook/paddle',
            '/webhook/stripe',
            'setup/*',
            'update/*',
            'logalert/*',
            'logparser/*',
            'osquery/*',
            'files/*',
            'stripe/*',
            'am/api/v2/public/*',
        ]);

        // Group 'web'
        $middleware->web(\RalphJSmit\Livewire\Urls\Middleware\LivewireUrlsMiddleware::class);
        $middleware->web(\App\Http\Middleware\RedirectToCyberBuddy::class);

        // Group 'api'
        $middleware->throttleApi();

        // Group 'saml'
        $middleware->group('saml', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        // Alias Middleware (used for some routes)
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);

        // Redirections
        $middleware->redirectGuestsTo(fn() => route('login'));
        $middleware->redirectUsersTo(AppServiceProvider::HOME);
    })
    // Disable Events discovery
    // Events and corresponding Listeners are listed in App\Providers\EventServiceProvider
    ->withEvents(false)
    // Enable discovery in Console\Commands
    ->withCommands()
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new Cleanup())->everyFifteenMinutes();
        $schedule->job(new DownloadDebianSecurityBugTracker())->daily();
        $schedule->command('telescope:prune --hours=48')->daily();

        // AdversaryMeter
        $schedule->job(new TriggerScan())->everyMinute();
        $schedule->job(new TriggerAssetsDiscovery())->daily();
        $schedule->job(new TriggerSendAuditReport())->weeklyOn(1 /* monday */, '6:45');

        // CyberBuddy
        $schedule->job(new EmbedChunks())->everyMinute();
        $schedule->job(new DeleteEmbeddedChunks())->everyMinute();
        $schedule->job(new UpdateTables())->everyMinute();
        $schedule->job(new RunScheduledTasks())->everyMinute();

        if (app()->environment('prod')) {
            $schedule->job(new ProcessIncomingEmails())->everyMinute();
        }

        // Health check - please let this at the end
        $schedule->command(DispatchQueueCheckJobsCommand::class)->everyMinute();
        $schedule->command(ScheduleCheckHeartbeatCommand::class)->everyMinute();

        $minutesToCron = function (int $minutes): string {
            return $minutes < 60
                ? "*/{$minutes} * * * *"
                : '0 */' . intdiv($minutes, 60) . ' * * *';
        };
        $schedule->command(RunLevelHealthChecksCommand::class, ['critical'])->cron($minutesToCron(config('health.level_refresh_intervals.critical')));
        $schedule->command(RunLevelHealthChecksCommand::class, ['medium'])->cron($minutesToCron(config('health.level_refresh_intervals.medium')));
        $schedule->command(RunLevelHealthChecksCommand::class, ['info'])->cron($minutesToCron(config('health.level_refresh_intervals.info')));

        // Misc. Wave
        // $schedule->command('inspire')->hourly();
        $schedule->command('subscriptions:cancel-expired')->hourly();
        $schedule->command('accounts:process-deletions')->daily();
        $schedule->command('activity:clean')->daily();

        // Health Check history cleaning
        $schedule->command('model:prune', ['--model' => [\Spatie\Health\Models\HealthCheckResultHistoryItem::class]])->daily();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
