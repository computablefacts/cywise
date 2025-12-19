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
    public function __invoke(Request $request, AssetsProcedure $procedure): View
    {
        $rows = $this->getRows($procedure->listGroups(new JsonRpcRequest())['groups'] ?? [], $procedure);
        
        Log::debug('rows=', $rows->toArray());

        return view('theme::iframes.shares', [
            'shares' => $rows,
        ]);
    }

    public function getRows(array $groups, AssetsProcedure $procedure): Collection{
        $rows = collect($groups)
            ->groupBy('hash')
            ->mapWithKeys(function (Collection $shares, string $group) use ($procedure) {
                $request = new JsonRpcRequest(['group' => $group]);
                $request->setUserResolver(fn() => $request->user());
                return [
                    $group => [
                        'group' => $group,
                        'tags' => $shares->map(fn($share) => $share['tags'])->unique()->flatten()->toArray(),
                        'nb_assets' => count($procedure->assetsInGroup($request)['assets'] ?? []),
                        'nb_vulnerabilities' => count($procedure->vulnerabilitiesInGroup($request)['vulnerabilities'] ?? []),
                        'target' => $shares->first()['created_by_email'] ?? __('Unknown'),
                    ],
                ];
            })
            ->values();
        return $rows;
    }
}