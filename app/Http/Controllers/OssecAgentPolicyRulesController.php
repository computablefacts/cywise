<?php

namespace App\Http\Controllers;

use App\Models\YnhOssecPolicy;
use App\Models\YnhServer;
use Illuminate\Http\JsonResponse;

class OssecAgentPolicyRulesController extends Controller
{
    public function __invoke(string $secret, string $policyUid): JsonResponse
    {
        /** @var ?YnhServer $server */
        $server = YnhServer::withoutGlobalScope('tenant_scope')
            ->where('secret', $secret)
            ->first();

        if (! $server) {
            return response()->json(['message' => 'Unknown server.'], 404);
        }

        /** @var ?YnhOssecPolicy $policy */
        $policy = YnhOssecPolicy::query()
            ->where('uid', $policyUid)
            ->first();

        if (! $policy) {
            return response()->json(['message' => 'Unknown OSSEC policy.'], 404);
        }

        if (preg_match('/^cywise_(\d+)_/', $policy->uid, $matches) === 1) {
            $policyTenantId = (int) $matches[1];
            $serverTenantId = (int) ($server->user()->value('tenant_id') ?? 0);

            if ($policyTenantId !== 0 && $policyTenantId !== $serverTenantId) {
                return response()->json(['message' => 'Unknown OSSEC policy.'], 404);
            }
        }

        if (! $policy->supportsPlatform($server->platform)) {
            return response()->json([
                'message' => 'The server is not compatible with this OSSEC policy.',
            ], 422);
        }

        return response()->json($policy->agentPayload());
    }
}
