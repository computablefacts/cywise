<?php

namespace App\Providers;

use App\Check\AssetsDiscoverCheck;
use App\Check\VulnerabilityScannerApiCheck;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseTableSizeCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class HealthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // See: https://spatie.be/docs/laravel-health/v1/available-checks/overview
        Health::checks([
            // Custom checks
            AssetsDiscoverCheck::new()->name('cywise.ioAssetsDiscover')
                ->domain('cywise.io'),
            VulnerabilityScannerApiCheck::new()->name('ApiVulnerabilityScanner'),

            // Standard checks
            QueueCheck::new()->name('QueueCritical')->onQueue('critical')
                ->failWhenHealthJobTakesLongerThanMinutes(2),
            QueueCheck::new()->name('QueueMedium')->onQueue('medium')
                ->failWhenHealthJobTakesLongerThanMinutes(5),
            QueueCheck::new()->name('QueueLow')->onQueue('low')
                ->failWhenHealthJobTakesLongerThanMinutes(10),
            QueueCheck::new()->name('QueueScout')->onQueue('scout')
                ->failWhenHealthJobTakesLongerThanMinutes(10),
            QueueCheck::new()->name('QueueDefault')->onQueue('default')
                ->failWhenHealthJobTakesLongerThanMinutes(10),
            CacheCheck::new(),
            DatabaseCheck::new(),
            DatabaseTableSizeCheck::new()
                ->table('telescope_entries', 6000),
            DebugModeCheck::new()->unless(app()->environment('local')),
            OptimizedAppCheck::new()->unless(app()->environment('local')),
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(2),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(80)
                ->failWhenUsedSpaceIsAbovePercentage(90),
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
