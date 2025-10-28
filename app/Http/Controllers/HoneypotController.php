<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Asset;

/** @deprecated */
class HoneypotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getVulnerabilitiesWithAssetInfo(?int $attackerId = null): array
    {
        return Asset::all()
            ->flatMap(function (Asset $asset) use ($attackerId) {
                return $asset->alerts()
                    ->get()
                    ->filter(fn(Alert $alert) => !$attackerId || ($alert->cve_id && $alert->events($attackerId)->exists()))
                    ->filter(fn(Alert $alert) => $attackerId || !$alert->is_hidden)
                    ->map(function (Alert $alert) use ($asset, $attackerId) {
                        return [
                            'alert' => $alert,
                            'asset' => $asset,
                            'port' => $alert->port(),
                            'events' => $alert->cve_id ? $alert->events($attackerId)->get()->toArray() : [],
                        ];
                    });
            })
            ->toArray();
    }

    public function getVulnerabilitiesWithAssetInfo2(string $assetBase64): array
    {
        return Asset::where('asset', base64_decode($assetBase64))
            ->get()
            ->flatMap(function (Asset $asset) {
                return $asset->alerts()
                    ->get()
                    ->map(function (Alert $alert) use ($asset) {
                        return [
                            'alert' => $alert,
                            'asset' => $asset,
                            'events' => $alert->events()->get()->toArray(),
                        ];
                    });
            })
            ->toArray();
    }

    public function getAlertStats(): array
    {
        $nbVulnerabilities = Asset::all()
            ->map(function (Asset $asset) {
                return [
                    'high' => $asset->alertsWithCriticalityHigh()->count(),
                    'high_unverified' => $asset->alertsWithCriticalityUnverified()->count(),
                    'medium' => $asset->alertsWithCriticalityMedium()->count(),
                    'low' => $asset->alertsWithCriticalityLow()->count(),
                ];
            })
            ->reduce(function (array $carry, array $counts) {
                return [
                    'high' => $carry['high'] + $counts['high'],
                    'high_unverified' => $carry['high_unverified'] + $counts['high_unverified'],
                    'medium' => $carry['medium'] + $counts['medium'],
                    'low' => $carry['low'] + $counts['low'],
                ];
            }, [
                'high' => 0,
                'high_unverified' => 0,
                'medium' => 0,
                'low' => 0,
            ]);
        return [
            'High' => $nbVulnerabilities['high'],
            'High (unverified)' => $nbVulnerabilities['high_unverified'],
            'Medium' => $nbVulnerabilities['medium'],
            'Low' => $nbVulnerabilities['low'],
        ];
    }
}
