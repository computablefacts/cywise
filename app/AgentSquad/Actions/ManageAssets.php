<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\ThoughtActionObservation;
use App\Http\Procedures\AssetsProcedure;
use App\Models\Asset;
use App\Models\User;
use App\Rules\IsValidAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManageAssets extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "manage_assets",
                "description" => "Manage assets such as domain names and IP addresses. This includes adding, removing and monitoring assets.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "input" => [
                            "type" => "string",
                            "description" => "
                                Provide the action to perform followed by the asset, using the format: 'action:asset'

                                For example:
                                - if the user's request is 'ajoute example.com', the input should be 'add:example.com'
                                - if the user's request is 'supprime 192.168.1.1', the input should be 'remove:192.168.1.1'
                                - if the user's request is 'surveille sub.domain.net', the input should be 'monitor:sub.domain.net'
                                - if the user's request is 'arrête la surveillance de 10.0.0.5', the input should be 'unmonitor:10.0.0.5'

                                The action (such as add, remove, monitor, or unmonitor) must come first, followed by a colon and then the asset (a domain or an IP address).
                            ",
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
        $chainOfThought = [
            new ThoughtActionObservation('This is an asset management task.', "{$this->name()}[{$action}, {$asset}]", 'Executing ManageAssets action.')
        ];

        if (!IsValidAsset::test($asset)) {
            return new FailedAnswer("Invalid asset. Please provide a valid domain or IP address.", $chainOfThought);
        }

        /** @var Asset $azzet */
        $azzet = Asset::where('asset', $asset)->first();

        if ($action === 'add') {
            if ($azzet) {
                return new FailedAnswer("Asset {$asset} already exists.", $chainOfThought);
            }
            $request = new Request(['asset' => $asset, 'watch' => false]);
            $request->setUserResolver(fn() => $user);
            $result = (new AssetsProcedure())->create($request);
            return new SuccessfulAnswer("Asset {$asset} added successfully.", $chainOfThought);
        }
        if (!$azzet) {
            return new FailedAnswer("Asset {$asset} does not exist.", $chainOfThought);
        }

        $procedure = new AssetsProcedure();
        $request = new Request(['asset_id' => $azzet->id]);
        $request->setUserResolver(fn() => $user);

        try {
            if ($action === 'remove') {
                $result = $procedure->unmonitor($request);
                $result = $procedure->delete($request);
                return new SuccessfulAnswer("Asset {$asset} removed successfully.", $chainOfThought);
            }
            if ($action === 'monitor') {
                $result = $procedure->monitor($request);
                return new SuccessfulAnswer("Asset {$asset} monitored successfully.", $chainOfThought);
            }
            if ($action === 'unmonitor') {
                $result = $procedure->unmonitor($request);
                return new SuccessfulAnswer("Asset {$asset} unmonitored successfully.", $chainOfThought);
            }
        } catch (\Exception $e) {
            return new FailedAnswer("Action {$action} failed when applied to asset {$asset}:\n\n{$e->getMessage()}", $chainOfThought);
        }
        return new FailedAnswer("Invalid action. Please use add, remove, monitor, or unmonitor.", $chainOfThought);
    }
}
