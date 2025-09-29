<?php

namespace App\Http\Procedures;

use App\Enums\OsqueryPlatformEnum;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use App\Models\YnhOsqueryRule;
use Illuminate\Validation\Rule;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class OsqueryRulesProcedure extends Procedure
{
    public static string $name = 'rules';

    #[RpcMethod(
        description: "Create a single Osquery rule.",
        params: [
            'name' => 'The rule name.',
            'description' => 'The rule description.',
            'category' => 'The rule category.',
            'platform' => 'The rule platform.',
            'interval' => 'The rule trigger interval in seconds.',
            'is_ioc' => 'true iif the rule is an indicator of compromise, false otherwise.',
            'score' => 'The score of the rule. Must be greater than 0 but no greater than 100 if is_ioc is true, and 0 otherwise.',
            'query' => 'The rule query.',
        ],
        result: [
            "rule" => "An Osquery rule.",
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        $user = $request->user();
        $params = $request->validate([
            'name' => $user->isCywiseAdmin() ?
                'required|string|min:2|max:191|regex:/^([0-9]+_cywise_)*[a-z]+[a-z0-9_]*[a-z0-9]+$/' :
                'required|string|min:2|max:191|regex:/^[a-z]+[a-z0-9_]*[a-z0-9]+$/',
            'description' => 'required|string|min:1|max:1000',
            'category' => 'required|string|min:1|max:191',
            'platform' => ['required', Rule::enum(OsqueryPlatformEnum::class)],
            'interval' => 'required|integer|min:0',
            'is_ioc' => 'required|boolean',
            'score' => 'required|integer|min:0|max:100',
            'query' => 'required|string|min:1|max:3000',
        ]);

        if ($params['is_ioc'] && $params['score'] <= 0) {
            throw new \Exception("The score must be greater than 0 but no greater than 100 if the rule is an indicator of compromise.");
        }
        if (!$params['is_ioc'] && $params['score'] > 0) {
            throw new \Exception("The score must be 0 if the rule is not an indicator of compromise.");
        }

        $name = $user->isCywiseAdmin() ? $params['name'] : "{$user->tenant_id}_cywise_{$params['name']}";

        /** @var ?YnhOsqueryRule $rule */
        $rule = YnhOsqueryRule::where('name', $name)
            ->where(function ($query) use ($user) {
                if (!$user->isCywiseAdmin()) {
                    $query->whereIn('created_by', User::where('tenant_id', $user->tenant_id)->pluck('id'));
                }
            })
            ->first();

        if ($rule) {
            $rule->description = $params['description'];
            $rule->comments = $params['description'];
            $rule->category = $params['category'];
            $rule->platform = $params['platform'];
            $rule->interval = $params['interval'];
            $rule->is_ioc = $params['is_ioc'];
            $rule->score = $params['score'];
            $rule->query = $params['query'];
            $rule->save();
        } else {
            $rule = YnhOsqueryRule::create([
                'name' => $name,
                'description' => $params['description'],
                'comments' => $params['description'],
                'category' => $params['category'],
                'platform' => $params['platform'],
                'interval' => $params['interval'],
                'is_ioc' => $params['is_ioc'],
                'score' => $params['score'],
                'query' => $params['query'],

                // App-specific fields
                'version' => '1.4.5',
                'snapshot' => false,
                'enabled' => true,
                'attck' => null,
                'created_by' => $user->isCywiseAdmin() ? null : $user->id,
            ]);
        }
        return [
            "rule" => $rule
        ];
    }

    #[RpcMethod(
        description: "Delete a single Osquery rule and all its associated data.",
        params: [
            "rule_id" => "The rule id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'rule_id' => 'required|integer|exists:ynh_osquery_rules,id',
        ]);

        $user = $request->user();

        YnhOsqueryRule::where('id', $params['rule_id'])
            ->where(function ($query) use ($user) {
                if (!$user->isCywiseAdmin()) {
                    $query->whereIn('created_by', User::where('tenant_id', $user->tenant_id)->pluck('id'));
                }
            })
            ->delete();

        return [
            "msg" => 'The rule has been removed!'
        ];
    }

    #[RpcMethod(
        description: "List the enabled Osquery rules.",
        params: [],
        result: [
            "rules" => "The list of enabled Osquery rules.",
        ]
    )]
    public function list(JsonRpcRequest $request): array
    {
        $user = $request->user();
        return [
            'rules' => YnhOsqueryRule::query()
                ->where('enabled', true)
                ->where(function ($query) use ($user) {
                    $query->whereNull('created_by');
                    if ($user && !$user->isCywiseAdmin()) {
                        $query->orWhereIn('created_by', User::where('tenant_id', $user->tenant_id)->pluck('id'));
                    }
                })
                ->get()
                ->sortBy(fn(YnhOsqueryRule $rule) => $rule->displayName(), SORT_NATURAL | SORT_FLAG_CASE),
        ];
    }
}