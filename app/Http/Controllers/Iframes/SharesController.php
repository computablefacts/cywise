<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SharesController extends Controller
{
    public function __invoke(Request $request): View
    {
        $procedure = new AssetsProcedure();
        $rows = collect($procedure->listGroups(new JsonRpcRequest())['groups'] ?? [])
            ->groupBy('hash')
            ->mapWithKeys(function (Collection $shares, string $group) use ($procedure) {
                // Log::debug('shares=', $shares->toArray());
                // $patrick1 = $shares->map(fn($share) => $share['tags'])->unique()->flatten();
                // Log::debug('tags=', $patrick1->toArray());
                $request = new JsonRpcRequest(['group' => $group]);
                $request->setUserResolver(fn() => $request->user());
                return [
                    $group => [
                        'group' => $group,
                        'tags' => $shares->map(fn($share) => $share['tags'])->unique()->flatten(),
                        'nb_assets' => count($procedure->assetsInGroup($request)['assets'] ?? []),
                        'nb_vulnerabilities' => count($procedure->vulnerabilitiesInGroup($request)['vulnerabilities'] ?? []),
                        //'target' => User::find($shares->first()['created_by'])?->email ?? __('Unknown'),
                    ],
                ];
            })
            ->values();
        
        // Log::debug('rows=', $rows->toArray());

        return view('theme::iframes.shares', [
            'shares' => $rows,
        ]);
    }
}