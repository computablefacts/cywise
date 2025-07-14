<?php

namespace App\Helpers\Agents;

use App\Http\Controllers\AssetController;
use App\Models\Asset;
use App\Models\User;

/** @deprecated */
class RemoveAsset extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "remove_asset",
                "description" => "Remove an existing asset from the user's network.",
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
        /** @var Asset $asset */
        $asset = Asset::where('asset', $this->args['asset'] ?? null)->firstOrFail();
        $controller = new AssetController();
        $response = $controller->assetMonitoringEnds($asset);
        $controller->deleteAsset($asset);
        $this->output = "The {$asset->asset} has been removed.";
        return $this;
    }
}
