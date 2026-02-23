<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use App\Models\Trial;
use Illuminate\Support\Str;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class RapidApiProcedure extends Procedure
{
    public static string $name = 'rapidapi';

    #[RpcMethod(
        description: "Trigger a vulnerability scan.",
        params: [
            'asset' => 'A domain or an IP address.',
        ],
        result: [
            "asset" => "The asset name. May be different from the one given in the request on ranges.",
            'modifications' => "The asset's creation and modification history.",
            'tags' => "The asset's tags.",
            'ports' => "The asset's open ports.",
            'vulnerabilities' => "The asset's vulnerabilities.",
            'timeline' => [
                'nmap' => [
                    'start' => "When the port scan started.",
                    'end' => "When the port scan ended.",
                ],
                'sentinel' => [
                    'start' => "When the vuln. scan started.",
                    'end' => "When the vuln. scan ended.",
                ],
                'next_scan' => "When the next scans will start.",
                'nb_vulns_scans_running' => "The number of running scans.",
                'nb_vulns_scans_completed' => "The number of completed scans.",
            ],
            'hiddenAlerts' => "The asset's hidden vulnerabilities (if any).",
        ]
    )]
    public function triggerScan(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'asset' => 'required|string|min:1|max:100'
        ]);

        /** @var User $user */
        $user = $request->user();
        $asset = Str::betweenFirst($params['asset'], '://', '/');
        $hash = md5($asset . now()->utc()->format('Y-m-d'));

        /** @var Trial $trial */
        $trial = Trial::updateOrCreate([
            'hash' => $hash
        ], [
            'domain' => $asset,
            'subdomains' => [$asset],
            'email' => $user->email,
            'honeypots' => false,
        ]);

        $procedure = new AssetsProcedure();

        if ($trial->wasRecentlyCreated && !$trial->completed) {
            $req = new JsonRpcRequest([
                'asset' => $asset,
                'watch' => true,
                'trial_id' => $trial->id,
            ]);
            $req->setUserResolver(fn() => $user);
            $procedure->create($req);
        }

        $req = new JsonRpcRequest([
            'trial_id' => $trial->id,
            'asset' => $asset
        ]);
        $req->setUserResolver(fn() => $user);
        return $procedure->get($req);
    }

    #[RpcMethod(
        description: "Trigger a vulnerability scan.",
        params: [
            'asset' => 'A domain or an IP address.',
        ],
        result: [
            "asset" => "The asset name. May be different from the one given in the request on ranges.",
            'modifications' => "The asset's creation and modification history.",
            'tags' => "The asset's tags.",
            'ports' => "The asset's open ports.",
            'vulnerabilities' => "The asset's vulnerabilities.",
            'timeline' => [
                'nmap' => [
                    'start' => "When the port scan started.",
                    'end' => "When the port scan ended.",
                ],
                'sentinel' => [
                    'start' => "When the vuln. scan started.",
                    'end' => "When the vuln. scan ended.",
                ],
                'next_scan' => "When the next scans will start.",
                'nb_vulns_scans_running' => "The number of running scans.",
                'nb_vulns_scans_completed' => "The number of completed scans.",
            ],
            'hiddenAlerts' => "The asset's hidden vulnerabilities (if any).",
        ]
    )]
    public function collectScanResults(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'asset' => 'required|string|min:1|max:100'
        ]);

        /** @var User $user */
        $user = $request->user();
        $asset = Str::betweenFirst($params['asset'], '://', '/');

        /** @var Trial $trial */
        $trial = Trial::query()
            ->where('domain', $asset)
            ->where('email', $user->email)
            ->where('updated_at', '>=', now()->subDays(2))
            ->firstOrFail();

        $req = new JsonRpcRequest([
            'trial_id' => $trial->id,
            'asset' => $asset
        ]);
        $req->setUserResolver(fn() => $user);
        return (new AssetsProcedure())->get($req);
    }
}
