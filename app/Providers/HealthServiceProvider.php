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
        Health::checks(array_merge(
            $this->getCriticalChecks(),
            $this->getMediumChecks(),
            $this->getInfoChecks()
        ));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Critical level checks - strictest thresholds for immediate alerts
     */
    protected function getCriticalChecks(): array
    {
        // See: https://spatie.be/docs/laravel-health/v1/available-checks/overview
        return [
            // Core services
            DatabaseCheck::new()->name('critical.DatabaseCheck'),
            CacheCheck::new()->name('critical.CacheCheck'),
            VulnerabilityScannerApiCheck::new()->name('critical.ApiVulnerabilityScanner'),

            // Queue checks
            QueueCheck::new()->name('critical.QueueCritical')
                ->onQueue('critical')
                ->failWhenHealthJobTakesLongerThanMinutes(25),
            QueueCheck::new()->name('critical.QueueMedium')
                ->onQueue('medium')
                ->failWhenHealthJobTakesLongerThanMinutes(100),
            QueueCheck::new()->name('critical.QueueLow')
                ->onQueue('low')
                ->failWhenHealthJobTakesLongerThanMinutes(200),
            QueueCheck::new()->name('critical.QueueScout')
                ->onQueue('scout')
                ->failWhenHealthJobTakesLongerThanMinutes(200),
            QueueCheck::new()->name('critical.QueueDefault')
                ->onQueue('default')
                ->failWhenHealthJobTakesLongerThanMinutes(100),

            // Schedule check
            ScheduleCheck::new()->name('critical.ScheduleCheck')
                ->heartbeatMaxAgeInMinutes(25),

            // Disk space
            UsedDiskSpaceCheck::new()->name('critical.UsedDiskSpaceCheck')
                ->warnWhenUsedSpaceIsAbovePercentage(95)
                ->failWhenUsedSpaceIsAbovePercentage(95),

            // Assets discover
            AssetsDiscoverCheck::new()->name('critical.cywise.ioAssetsDiscover')
                ->domain('cywise.io')
                ->warnAfterSeconds(90)
                ->failAfterSeconds(90),
        ];
    }

    /**
     * Medium level checks - normal thresholds for standard operations
     */
    protected function getMediumChecks(): array
    {
        // See: https://spatie.be/docs/laravel-health/v1/available-checks/overview
        return [
            // Queue checks
            QueueCheck::new()->name('medium.QueueCritical')
                ->onQueue('critical')
                ->failWhenHealthJobTakesLongerThanMinutes(5),
            QueueCheck::new()->name('medium.QueueMedium')
                ->onQueue('medium')
                ->failWhenHealthJobTakesLongerThanMinutes(20),
            QueueCheck::new()->name('medium.QueueLow')
                ->onQueue('low')
                ->failWhenHealthJobTakesLongerThanMinutes(40),
            QueueCheck::new()->name('medium.QueueScout')
                ->onQueue('scout')
                ->failWhenHealthJobTakesLongerThanMinutes(40),
            QueueCheck::new()->name('medium.QueueDefault')
                ->onQueue('default')
                ->failWhenHealthJobTakesLongerThanMinutes(20),

            // Schedule check
            ScheduleCheck::new()->name('medium.ScheduleCheck')
                ->heartbeatMaxAgeInMinutes(5),

            // Disk space
            UsedDiskSpaceCheck::new()->name('medium.UsedDiskSpaceCheck')
                ->warnWhenUsedSpaceIsAbovePercentage(90)
                ->failWhenUsedSpaceIsAbovePercentage(90),

            // Assets discover
            AssetsDiscoverCheck::new()->name('medium.cywise.ioAssetsDiscover')
                ->domain('cywise.io')
                ->warnAfterSeconds(60)
                ->failAfterSeconds(60),

            // Database table size for telescope
            DatabaseTableSizeCheck::new()->name('medium.DatabaseTableSizeCheck')
                ->table('telescope_entries', 6000),

            // Debug and optimization checks
            DebugModeCheck::new()->name('medium.DebugModeCheck')
                ->unless(app()->environment('local')),
            OptimizedAppCheck::new()->name('medium.OptimizedAppCheck')
                ->unless(app()->environment('local')),
        ];
    }

    /**
     * Info level checks - relaxed thresholds for monitoring
     */
    protected function getInfoChecks(): array
    {
        // See: https://spatie.be/docs/laravel-health/v1/available-checks/overview
        return [
            // Disk space
            UsedDiskSpaceCheck::new()->name('info.UsedDiskSpaceCheck')
                ->warnWhenUsedSpaceIsAbovePercentage(80)
                ->failWhenUsedSpaceIsAbovePercentage(80),

            // Database table size for telescope
            DatabaseTableSizeCheck::new()->name('info.DatabaseTableSizeCheck')
                ->table('telescope_entries', 4000),
        ];
    }
}
