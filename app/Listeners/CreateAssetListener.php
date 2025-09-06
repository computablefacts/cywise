<?php

namespace App\Listeners;

use App\Enums\AssetTypesEnum;
use App\Events\CreateAsset;
use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\User;
use App\Models\YnhTrial;
use App\Rules\IsValidAsset;
use App\Rules\IsValidDomain;
use App\Rules\IsValidIpAddress;
use App\Rules\IsValidTag;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateAssetListener extends AbstractListener
{
    public static function execute(User $user, string $asset, bool $monitor, array $tags = [], int $trialId = 0): ?Asset
    {
        if (!IsValidAsset::test($asset)) {
            Log::error("Invalid asset : {$asset}");
            return null;
        }
        if (IsValidDomain::test($asset)) {
            $assetType = AssetTypesEnum::DNS;
        } elseif (IsValidIpAddress::test($asset)) {
            $assetType = AssetTypesEnum::IP;
        } else {
            $assetType = AssetTypesEnum::RANGE;
        }
        if ($trialId > 0) {
            /** @var YnhTrial $trial */
            $trial = YnhTrial::where('id', $trialId)->firstOrFail();
            /** @var Asset $azzet */
            $azzet = Asset::where('asset', $asset)
                ->where('created_by', $trial->created_by)
                ->where('ynh_trial_id', $trial->id)
                ->first();
        } else {
            /** @var Asset $azzet */
            $azzet = Asset::where('asset', $asset)
                ->where('created_by', $user->id)
                ->whereNull('ynh_trial_id')
                ->first();
        }
        if (!$azzet) {
            $azzet = Asset::create([
                'asset' => $asset,
                'type' => $assetType,
                'is_monitored' => $monitor,
                'created_by' => $user->id,
                'ynh_trial_id' => $trialId > 0 ? $trialId : null,
            ]);
            collect($tags)->map(fn(string $tag) => Str::lower($tag))
                ->filter(fn(string $tag) => IsValidTag::test($tag))
                ->each(function (string $tag) use ($azzet) {
                    /** @var ?AssetTag $obj */
                    $obj = $azzet->tags()->where('tag', $tag)->first();
                    if (!$obj) {
                        $obj = $azzet->tags()->create(['tag' => $tag]);
                    }
                });
        }
        return $azzet;
    }

    public function viaQueue(): string
    {
        return self::CRITICAL;
    }

    protected function handle2($event)
    {
        if (!($event instanceof CreateAsset)) {
            throw new \Exception('Invalid event type!');
        }
        $event->user->actAs(); // otherwise the tenant will not be properly set
        self::execute($event->user, $event->asset, $event->monitor, $event->tags);
    }
}
