<?php

namespace App\Http\Procedures;

use App\Enums\OsqueryPlatformEnum;
use App\Helpers\OssecRulesParser;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use App\Models\YnhOssecCheck;
use App\Models\YnhOssecPolicy;
use Illuminate\Validation\Rule;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class OssecRulesProcedure extends Procedure
{
    public static string $name = 'sca';

    #[RpcMethod(
        description: "Create a single OSSEC rule.",
        params: [
            'name' => 'The rule name.',
            'description' => 'The rule description.',
            'rationale' => 'The reason behind this rule.',
            'rule' => 'The rule checks.',
        ],
        result: [
            "rule" => "An OSSEC rule.",
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        $user = $request->user();
        $params = $request->validate([
            'name' => 'required|string|min:2|max:500',
            'description' => 'required|string|min:1|max:5000',
            'rationale' => 'required|string|min:1|max:2000',
            'remediation' => 'nullable|string|min:1|max:3000',
            'platform' => ['required', Rule::enum(OsqueryPlatformEnum::class)],
            'rule' => 'required|string|min:1|max:5000',
        ]);

        /** @var string $platform */
        $platform = $params['platform'];
        $requirements = OssecRulesParser::parse($params['rule']);

        /** @var YnhOssecPolicy $policy */
        $policy = YnhOssecPolicy::updateOrCreate([
            'uid' => 'cywise' . ($user->isCywiseAdmin() ? '_0_' : "_{$user->tenant_id}_") . $platform,
        ], [
            'name' => $platform,
            'description' => $user->isCywiseAdmin() ? "Policies for Cywise and platform {$platform}." : "Policies for tenant {$user->tenant_id} and platform {$platform}.",
            'references' => [],
            'requirements' => [],
        ]);

        /** @var ?YnhOssecCheck $check */
        $check = YnhOssecCheck::where('title', $params['name'])
            ->where('ynh_ossec_policy_id', $policy->id)
            ->where(function ($query) use ($user) {
                if (!$user->isCywiseAdmin()) {
                    $query->whereIn('created_by', User::where('tenant_id', $user->tenant_id)->pluck('id'));
                }
            })
            ->first();

        if ($check) {
            $check->description = $params['description'];
            $check->rationale = $params['rationale'];
            $check->remediation = $params['remediation'];
            $check->rule = $params['rule'];
            $check->requirements = $requirements;
            $check->save();
        } else {
            $check = YnhOssecCheck::create([
                'ynh_ossec_policy_id' => $policy->id,
                'uid' => YnhOssecCheck::max('uid') + 1,
                'title' => $params['name'],
                'description' => $params['description'],
                'rationale' => $params['rationale'],
                'remediation' => $params['remediation'],
                'rule' => $params['rule'],

                // App-specific fields
                'requirements' => $requirements,
                'impact' => '',
                'references' => [],
                'compliance' => [[
                    'cywise' => '0.1',
                ]],
            ]);
        }
        return [
            "rule" => $check
        ];
    }

    #[RpcMethod(
        description: "Delete a single OSSEC rule and all its associated data.",
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
            'rule_id' => 'required|integer|exists:ynh_ossec_checks,id',
        ]);

        $user = $request->user();

        YnhOssecCheck::where('id', $params['rule_id'])
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

}
