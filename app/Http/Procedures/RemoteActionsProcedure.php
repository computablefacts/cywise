<?php

namespace App\Http\Procedures;

use App\AgentSquad\ActionsRegistry;
use App\Http\Requests\JsonRpcRequest;
use App\Models\ActionSetting;
use App\Models\RemoteAction;
use App\Models\User;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class RemoteActionsProcedure extends Procedure
{
    public static string $name = 'remoteactions';

    #[RpcMethod(
        description: "Save action settings for tenant or user scope.",
        params: [
            "scope_type" => "Scope type: 'tenant' or 'user'.",
            "scope_id" => "The tenant id or the user id depending on scope_type.",
            "actions" => "Array of action names to enable (others will be disabled).",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function saveSettings(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'scope_type' => 'required|string|in:tenant,user',
            'scope_id' => 'required|integer|min:0',
            'actions' => 'array',
            'actions.*' => 'string',
        ]);
        $user = $request->user();
        $scopeType = $params['scope_type'];
        $scopeId = (int)$params['scope_id'];

        // Ensure scope is within current tenant
        if ($scopeType === 'tenant') {
            abort_if($scopeId !== $user->tenant_id, 403);
        } else {
            /** @var User $targetUser */
            $targetUser = User::findOrFail($scopeId);
            abort_unless($targetUser->tenant_id === $user->tenant_id, 403);
        }

        $enabledList = collect($params['actions'] ?? []);
        $actions = ActionsRegistry::all();

        foreach ($actions as $actionName => $action) {
            $enabled = $enabledList->contains($actionName);
            /** @var ActionSetting $setting */
            $setting = ActionSetting::firstOrNew([
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'action' => $actionName,
            ]);
            $setting->enabled = $enabled;
            $setting->save();
        }
        return [
            'msg' => __('Settings saved.'),
        ];
    }

    #[RpcMethod(
        description: "Create a single remote action.",
        params: [
            "name" => "The action name.",
            "description" => "The action description.",
            "url" => "The action URL.",
            "headers" => "The action headers.",
            "schema" => "The action schema.",
            "payload_template" => "The action payload template.",
            "response_template" => "The action response template.",
            "examples" => "The action examples.",
        ],
        result: [
            "action" => "A remote action.",
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        $user = $request->user();
        $params = $request->validate([
            'name' => 'required|string|min:2|max:191|regex:/^[a-z]+[a-z0-9_]*[a-z0-9]+$/',
            'description' => 'required|string|min:2|max:2048',
            'url' => 'required|string|max:2048',
            'headers' => 'nullable|array',
            'schema' => 'nullable|array',
            'payload_template' => 'nullable|array',
            'response_template' => 'nullable|string',
            'examples' => 'nullable|array',
        ]);

        // Hard block obvious executable / dangerous patterns
        $blacklist = [
            '/<\?(?:php)?/i',
            '/@(?:php|include|extends|section|yield|stack|inject)\b/i',
            '/\b(?:env|config|app|DB|Auth|request|session|view|resolve|resolveFacade|call_user_func|eval)\s*\(/i',
            '/->\s*[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', // method calls like $x->foo()
            '/::\s*[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', // static calls like Class::foo()
            '/\bfunction\b/i', // function declarations
            '/\bfn\s*\(/i', // arrow functions
        ];

        foreach ($blacklist as $pattern) {
            if (preg_match($pattern, $params['response_template'])) {
                throw new \Exception('This pattern is not allowed in response_template: ' . $pattern);
            }
        }

        /** @var RemoteAction $action */
        $action = RemoteAction::where('name', $params['name'])->first();

        if ($action) {
            $action->name = $params['name'];
            $action->description = $params['description'];
            $action->url = $params['url'];
            $action->headers = $params['headers'] ?? [];
            $action->schema = $params['schema'] ?? [];
            $action->payload_template = $params['payload_template'] ?? [];
            $action->response_template = $params['response_template'] ?? '';
            $action->examples = $params['examples'] ?? [];
            $action->save();
        } else {
            $action = RemoteAction::create([
                'name' => $params['name'],
                'description' => $params['description'],
                'url' => $params['url'],
                'headers' => $params['headers'] ?? [],
                'schema' => $params['schema'] ?? [],
                'payload_template' => $params['payload_template'] ?? [],
                'response_template' => $params['response_template'] ?? '',
                'examples' => $params['examples'] ?? [],
                'created_by' => $user->isCywiseAdmin() ? null : $user->id,
            ]);
        }
        return [
            'action' => $action,
        ];
    }

    #[RpcMethod(
        description: "Delete a remote action.",
        params: [
            "action_id" => "The action identifier.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'action_id' => 'required|integer|exists:cb_remote_actions,id',
        ]);

        $user = $request->user();

        RemoteAction::where('id', $params['action_id'])
            ->where(function ($query) use ($user) {
                if (!$user->isCywiseAdmin()) {
                    $query->whereIn('created_by', User::where('tenant_id', $user->tenant_id)->pluck('id'));
                }
            })
            ->delete();

        return [
            'msg' => __('The action has been removed!'),
        ];
    }
}