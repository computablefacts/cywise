<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SharesController extends Controller
{
    public function __invoke(Request $request): View
    {
        $procedure = new AssetsProcedure();
        $rows = collect($procedure->listGroups(new JsonRpcRequest())['groups'] ?? [])
            ->groupBy('hash')
            ->mapWithKeys(function (Collection $tags, string $group) use ($procedure) {
                $request = new JsonRpcRequest(['group' => $group]);
                $request->setUserResolver(fn() => $request->user());
                return [
                    $group => [
                        'group' => $group,
                        'tags' => $tags->map(fn(array $tag) => $tag['tag'])->unique(),
                        'nb_assets' => count($procedure->assetsInGroup($request)['assets'] ?? []),
                        'nb_vulnerabilities' => count($procedure->vulnerabilitiesInGroup($request)['vulnerabilities'] ?? []),
                        'target' => User::find($tags->first()['created_by'])?->email ?? __('Unknown'),
                    ],
                ];
            })
            ->values();
        return view('theme::iframes.shares', [
            'shares' => $rows,
        ]);
    }
}