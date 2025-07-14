<?php

namespace App\Helpers\Agents;

use App\Http\Controllers\AssetController;
use App\Models\User;
use Illuminate\Http\Request;

/** @deprecated */
class BeginAssetMonitoring extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "begin_asset_monitoring",
                "description" => "Monitor a new or an existing asset within the user's network.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "asset" => [
                            "type" => ["string"],
                            "description" => "The asset's IP address, domain or subdomain.",
                        ],
                    ],
                    "required" => ["asset"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct(User $user, string $threadId, array $messages, array $args = [])
    {
        parent::__construct($user, $threadId, $messages, $args);
    }

    function execute(): AbstractAction
    {
        $asset = $this->args['asset'] ?? null;
        $request = new Request([
            'asset' => $asset,
            'watch' => true,
        ]);
        $request->setUserResolver(fn() => auth()->user());
        $response = (new AssetController())->saveAsset($request);
        $this->output = "Congratulation! The monitoring of $asset started.";
        return $this;
    }
}
