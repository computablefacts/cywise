<?php

namespace App\Listeners;

use App\Events\BeginPortsScan;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Scan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BeginPortsScanListener extends AbstractListener
{
    public function viaQueue(): string
    {
        return self::MEDIUM;
    }

    protected function handle2($event)
    {
        if (!($event instanceof BeginPortsScan)) {
            throw new \Exception('Invalid event type!');
        }

        $asset = $event->asset();

        if (!$asset) {
            Log::warning("Asset has been removed : {$event->assetId}");
            return;
        }

        $task = $this->beginTask($asset->asset);
        $taskId = $task['task_id'] ?? null;

        if (!$taskId) {
            Log::error('Ports scan cannot be started : ' . json_encode($task));
        } else {

            /** @var Scan $scan */
            $scan = Scan::create([
                'asset_id' => $asset->id,
                'ports_scan_id' => $taskId,
                'ports_scan_begins_at' => Carbon::now(),
            ]);

            $asset->next_scan_id = $taskId;
            $asset->save();

            // TODO : EndPortsScan::dispatch(Carbon::now(), $asset, $scan);
        }
    }

    private function beginTask(string $asset): array
    {
        return ApiUtils::task_nmap_public($asset);
    }
}
