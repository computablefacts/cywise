<?php

namespace App\Listeners;

use App\Enums\AssetTypesEnum;
use App\Events\DeleteAsset;
use App\Models\Asset;
use App\Models\User;
use App\Rules\IsValidAsset;
use App\Rules\IsValidDomain;
use App\Rules\IsValidIpAddress;
use Illuminate\Support\Facades\Log;

class DeleteAssetListener extends AbstractListener
{
    public static function execute(User $user, string $asset): bool
    {
        if (!IsValidAsset::test($asset)) {
            Log::error("Invalid asset : {$asset}");
            return false;
        }
        if (IsValidDomain::test($asset)) {
            $assetType = AssetTypesEnum::DNS;
        } elseif (IsValidIpAddress::test($asset)) {
            $assetType = AssetTypesEnum::IP;
        } else {
            $assetType = AssetTypesEnum::RANGE;
        }

        // TimelineItem::deleteAlerts($user->id, $asset);

        Asset::where('asset', $asset)
            ->where('type', $assetType)
            ->where('created_by', $user->id)
            ->delete();

        return true;
    }

    public function viaQueue(): string
    {
        return self::CRITICAL;
    }

    protected function handle2($event)
    {
        if (!($event instanceof DeleteAsset)) {
            throw new \Exception('Invalid event type!');
        }
        $event->user->actAs(); // otherwise the tenant will not be properly set
        self::execute($event->user, $event->asset);
    }
}
