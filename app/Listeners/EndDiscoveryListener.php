<?php

namespace App\Listeners;

use App\Events\CreateAsset;
use App\Events\EndDiscovery;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Asset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EndDiscoveryListener extends AbstractListener
{
    public function viaQueue(): string
    {
        return self::LOW;
    }

    protected function handle2($event)
    {
        if (!($event instanceof EndDiscovery)) {
            throw new \Exception('Invalid event type!');
        }

        $tld = $event->tld;
        $taskId = $event->taskId;
        $dropEvent = $event->drop();

        if ($dropEvent) {
            Log::error("Discovery event is too old : {$tld}");
            Asset::where('discovery_id', $taskId)->update(['discovery_id', null]);
            return;
        }

        $task = $this->taskStatus($taskId);
        $taskStatus = $task['task_status'] ?? null;

        // The task is running: try again later
        if (!$taskStatus) {
            $event->sink();
            return;
        }

        // The task ended with an error
        if ($taskStatus !== 'SUCCESS') {
            if ($taskStatus === 'FAILURE') {
                Log::error('Assets discovery failed : ' . json_encode($task));
                Asset::where('discovery_id', $taskId)->update(['discovery_id', null]);
                return;
            }
            $event->sink();
            return;
        }

        $taskOutput = $this->taskOutput($taskId);

        if (!isset($taskOutput['task_result'])) {
            Log::error('Assets discovery failed : ' . json_encode($taskOutput));
            Asset::where('discovery_id', $taskId)->update(['discovery_id', null]);
            return;
        }

        $assets = json_decode($taskOutput['task_result'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Assets discovery failed : ' . json_encode($taskOutput));
            Asset::where('discovery_id', $taskId)->update(['discovery_id', null]);
            return;
        }

        collect($assets)
            ->map(fn(array $asset) => $asset['domain'])
            ->filter(fn(string $domain) => !empty($domain))
            ->each(function (string $domain) use ($tld, $taskId) {
                // TODO : backport confidence score $asset['score'] / 100
                Asset::where('tld', $tld)
                    ->get()
                    ->filter(function (Asset $asset) {
                        // Deal with clients using one of our many domains...
                        return $asset->createdBy->isCywiseAdmin() || !Str::endsWith($asset->asset, ['computablefacts.com', 'computablefacts.io', 'towerify.io', 'cywise.io']);
                    })
                    ->each(function (Asset $asset) use ($domain) {
                        CreateAsset::dispatch($asset->createdBy, $domain, true);
                    });
            });

        Asset::where('discovery_id', $taskId)->update(['discovery_id', null]);
    }

    private function taskStatus(string $taskId): array
    {
        return ApiUtils::task_status_public($taskId);
    }

    private function taskOutput(string $taskId): array
    {
        return ApiUtils::task_result_public($taskId);
    }
}
