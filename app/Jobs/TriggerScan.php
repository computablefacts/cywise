<?php

namespace App\Jobs;

use App\Events\BeginPortsScan;
use App\Listeners\BeginPortsScanListener;
use App\Models\Asset;
use App\Models\Scan;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TriggerScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 3 * 180; // 9mn

    public function __construct()
    {
        //
    }

    public function handle()
    {
        $frequency = config('towerify.adversarymeter.days_between_scans');
        $minDate = Carbon::now()->subDays((int)$frequency);

        // Remove running scans that are not in the 'prev', 'cur' or 'next' status
        Scan::removeDanglingScans();

        // Begin a new scan
        Asset::whereNull('next_scan_id')
            ->where('is_monitored', true)
            ->whereNull('ynh_trial_id')
            ->get()
            ->concat(
                Asset::select('am_assets.*')
                    ->join('ynh_trials', 'ynh_trials.id', '=', 'am_assets.ynh_trial_id')
                    ->whereNull('am_assets.next_scan_id')
                    ->where('am_assets.is_monitored', true)
                    ->where('ynh_trials.completed', false)
                    ->get()
            )
            ->filter(function (Asset $asset) use ($minDate) {
                $scans = $asset->scanCompleted();
                return $scans->isEmpty() || $scans->sortBy('vulns_scan_ends_at')->last()?->vulns_scan_ends_at <= $minDate;
            })
            ->each(function (Asset $asset) {
                Log::debug("Starting scan of asset {$asset->asset} ({$asset->id})...");
                (new BeginPortsScanListener())->handle(new BeginPortsScan($asset));
                Log::debug("Scan of asset {$asset->asset} ({$asset->id}) started.");
            });
    }
}
