<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Asset;
use App\Models\User;
use App\Rules\IsValidAsset;
use Illuminate\Support\Str;

class ManageAssets extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "manage_assets",
                "description" => "
                    Manage assets such as domain names and IP addresses. This includes adding, removing and monitoring assets.
                    Provide the action to perform followed by the asset, using the format: 'action:asset'.
                    The action (such as add, remove, monitor, or unmonitor) must come first, followed by a colon and then the asset (a domain name or an IP address).
                    For example:
                    - if the request is 'ajoute example.com', the input should be 'add:example.com'
                    - if the request is 'supprime 192.168.1.1', the input should be 'remove:192.168.1.1'
                    - if the request is 'surveille sub.domain.net', the input should be 'monitor:sub.domain.net'
                    - if the request is 'arrête la surveillance de 10.0.0.5', the input should be 'unmonitor:10.0.0.5'
                ",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "input" => [
                            "type" => "string",
                            "description" => "The action to perform followed by the asset, using the format: 'action:asset'.",
                        ],
                    ],
                    "required" => ["input"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct()
    {
        //
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        $action = Str::trim(Str::before($input, ':'));
        $asset = Str::trim(Str::after($input, ':'));

        if (!IsValidAsset::test($asset)) {
            return new FailedAnswer("Invalid asset. Please provide a valid domain or IP address.");
        }

        /** @var Asset $azzet */
        $azzet = Asset::where('asset', $asset)->first();

        if ($action === 'add') {
            if ($azzet) {
                return new FailedAnswer("Asset {$asset} already exists.");
            }
            $request = new JsonRpcRequest(['asset' => $asset, 'watch' => false]);
            $request->setUserResolver(fn() => $user);
            $result = (new AssetsProcedure())->create($request);
            return new SuccessfulAnswer("Asset {$asset} added successfully.");
        }
        if (!$azzet) {
            return new FailedAnswer("Asset {$asset} does not exist.");
        }

        $procedure = new AssetsProcedure();
        $request = new JsonRpcRequest(['asset_id' => $azzet->id]);
        $request->setUserResolver(fn() => $user);

        try {
            if ($action === 'remove') {
                $result = $procedure->unmonitor($request);
                $result = $procedure->delete($request);
                return new SuccessfulAnswer("Asset {$asset} removed successfully.");
            }
            if ($action === 'monitor') {
                $result = $procedure->monitor($request);
                return new SuccessfulAnswer("Asset {$asset} monitored successfully.");
            }
            if ($action === 'unmonitor') {
                $result = $procedure->unmonitor($request);
                return new SuccessfulAnswer("Asset {$asset} unmonitored successfully.");
            }
        } catch (\Exception $e) {
            return new FailedAnswer("Action {$action} failed when applied to asset {$asset}:\n\n{$e->getMessage()}");
        }
        return new FailedAnswer("Invalid action. Please use add, remove, monitor, or unmonitor.");
    }
}
