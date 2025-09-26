<?php

namespace App\Listeners;

use App\Check\AssetsDiscoverCheck;
use App\Events\StartAssetsDiscover;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Requests\JsonRpcRequest;
use Illuminate\Support\Facades\Log;

class StartAssetsDiscoverListener extends AbstractListener
{
    public function viaQueue(): string
    {
        return self::LOW;
    }

    protected function handle2($event)
    {
        if (!($event instanceof StartAssetsDiscover)) {
            throw new \Exception('Invalid event type!');
        }

        /** @var AssetsDiscoverCheck $check */
        $check = $event->check;

        $check->setLastStart();

        $start = microtime(true);

        ApiUtils::timeout(round($check->getFailedDurationThresholdSeconds() * 1.5));
        $request = new JsonRpcRequest(['domain' => $check->getDomain()]);
        try {
            $response = (new AssetsProcedure())->discover($request);
        } catch (\Exception $e) {
            Log::debug($e);
            $response = [];
        }
        $check->setLastResponse($response);

        $end = microtime(true);

        $check->setLastDuration($end - $start);
    }
}
