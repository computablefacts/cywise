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
    public static string $name = 'ossec';

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

        /** @var string $title */
        $title = $params['name'];
        /** @var string $platform */
        $platform = $params['platform'];
        $requirements = OssecRulesParser::parse($params['rule']);

        /** @var YnhOssecPolicy $policy */
        $policy = YnhOssecPolicy::updateOrCreate([
            'uid' => $this->buildPolicyUid($user, $platform),
        ], [
            'name' => $platform,
            'description' => $user->isCywiseAdmin() ? "Policies for Cywise and platform {$platform}." : "Policies for tenant {$user->tenant_id} and platform {$platform}.",
            'references' => [],
            'requirements' => [],
        ]);

        /** @var ?YnhOssecCheck $check */
        $check = YnhOssecCheck::where('title', $title)
            ->where('ynh_ossec_policy_id', $policy->id)
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
                'title' => $title,
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

        YnhOssecCheck::select('ynh_ossec_checks.*')
            ->where('ynh_ossec_checks.id', $params['rule_id'])
            ->join('ynh_ossec_policies', 'ynh_ossec_policies.id', '=', 'ynh_ossec_checks.ynh_ossec_policy_id')
            ->whereLike('ynh_ossec_policies.uid', $this->buildPolicyUid($user) . "%")
            ->delete();

        return [
            "msg" => 'The rule has been removed!'
        ];
    }

    #[RpcMethod(
        description: "List the OSSEC rules.",
        params: [],
        result: [
            "rules" => "The list of OSSEC rules.",
        ]
    )]
    public function list(JsonRpcRequest $request): array
    {
        $user = $request->user();
        return [
            'rules' => YnhOssecCheck::select('ynh_ossec_checks.*')
                ->join('ynh_ossec_policies', 'ynh_ossec_policies.id', '=', 'ynh_ossec_checks.ynh_ossec_policy_id')
                ->whereLike('ynh_ossec_policies.uid', $this->buildPolicyUid($user) . "%")
                ->get()
                ->sortBy(fn(YnhOssecCheck $rule) => $rule->title, SORT_NATURAL | SORT_FLAG_CASE),
        ];
    }

    private function buildPolicyUid(User $user, ?string $platform = null): string
    {
        return 'cywise' . ($user->isCywiseAdmin() ? '_0_' : "_{$user->tenant_id}_") . ($platform ?? '');
    }
}
