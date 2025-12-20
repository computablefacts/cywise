<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\Enums\AssetTypesEnum;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use Illuminate\Support\Str;

class ListAssets extends AbstractAction
{
    protected function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "list_assets",
                "description" => "
Retrieve the list of assets (domains or IP addresses) based on their monitoring status and type.
Provide the action to perform followed by the asset status (monitored/monitorable/any) and the asset type (domain/ip/any), using the format: 'action:status:type'.
The action (always list) must come first, followed by a colon and then the asset status (monitored/monitorable/any), followed by a colon and then the asset type (domain/ip/any).
For example:
- if the request is 'quels sont mes actifs ?', the input should be 'list:any:any'
- if the request is 'quels sont mes actifs surveillés ?', the input should be 'list:monitored:any'
- if the request is 'quels sont mes domaines surveillés ?', the input should be 'list:monitored:domain'
- if the request is 'quels sont mes IP surveillées ?', the input should be 'list:monitored:ip'
- if the request is 'quels sont mes actifs à surveiller ?', the input should be 'list:monitorable:any'
- if the request is 'quels sont mes domaines à surveiller ?', the input should be 'list:monitorable:domain'
- if the request is 'quels sont mes IP à surveiller ?', the input should be 'list:monitorable:ip'
                ",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "input" => [
                            "type" => "string",
                            "description" => "The action to perform followed by the asset status (monitored/monitorable/any) and the asset type (domain/ip/any), using the format: 'action:status:type'.",
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
        $status = Str::trim(Str::between($input, ':', ':'));
        $category = Str::trim(Str::afterLast($input, ':'));

        if ($action !== 'list') {
            return new FailedAnswer(__("Invalid action. Please use list."));
        }
        if (!in_array($status, ['monitored', 'monitorable', 'any'])) {
            return new FailedAnswer(__("Invalid status. Please use monitored, monitorable, or any."));
        }
        if (!in_array($category, ['domain', 'ip', 'any'])) {
            return new FailedAnswer(__("Invalid category. Please use domain, ip, or any."));
        }

        $procedure = new AssetsProcedure();
        if ($status === 'monitored') {
            $request = new JsonRpcRequest(['is_monitored' => true]);
        } else if ($status === 'monitorable') {
            $request = new JsonRpcRequest(['is_monitored' => false]);
        } else {
            $request = new JsonRpcRequest();
        }
        $request->setUserResolver(fn() => $user);

        try {
            $assets = collect($procedure->list($request)['assets'] ?? [])->filter(function (array $asset) use ($category) {
                if ($category === 'domain') {
                    return $asset['type'] === AssetTypesEnum::DNS->name;
                }
                if ($category === 'ip') {
                    return $asset['type'] === AssetTypesEnum::IP->name;
                }
                return true;
            });
            if ($assets->isEmpty()) {
                return new SuccessfulAnswer("There are no assets of category '{$category}' and status '{$status}'.");
            }
            if ($assets->count() === 1) {

                $status = $status === 'any' ? '' : $status;
                $category = $category === 'any' ? 'asset' : ($category === 'domain' ? 'domain' : 'IP address');
                $preamble = "1 {$status} {$category} has been found:";
                $list = $assets->map(fn(array $asset) => "- {$asset['asset']}")->implode("\n");

                return new SuccessfulAnswer("{$preamble}\n\n{$list}");
            }

            $status = $status === 'any' ? '' : $status;
            $category = $category === 'any' ? 'assets' : ($category === 'domain' ? 'domains' : 'IP addresses');
            $preamble = "{$assets->count()} {$status} {$category} have been found:";
            $list = $assets->map(fn(array $asset) => "- {$asset['asset']}")->implode("\n");

            return new SuccessfulAnswer("{$preamble}\n\n{$list}");
        } catch (\Exception $e) {
            return new FailedAnswer(__("Action :action failed when applied to assets of category :category and status :status:\n\n:msg", ['action' => $action, 'category' => $category, 'status' => $status, 'msg' => $e->getMessage()]));
        }
    }
}
