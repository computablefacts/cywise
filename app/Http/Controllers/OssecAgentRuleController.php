<?php

namespace App\Http\Controllers;

use App\Enums\OsqueryPlatformEnum;
use App\Models\YnhOssecCheck;
use App\Models\YnhServer;
use Illuminate\Http\JsonResponse;

class OssecAgentRuleController extends Controller
{
    private const string UNIX_POLICY_UID = 'cywise_ossec_unix';

    public function __invoke(string $secret, int $ruleUid): JsonResponse
    {
        /** @var ?YnhServer $server */
        $server = YnhServer::withoutGlobalScope('tenant_scope')
            ->where('secret', $secret)
            ->first();

        if (!$server) {
            return response()->json(['message' => 'Unknown server.'], 404);
        }

        if (!in_array($server->platform, [
            OsqueryPlatformEnum::LINUX,
            OsqueryPlatformEnum::POSIX,
            OsqueryPlatformEnum::UBUNTU,
        ], true)) {
            return response()->json(['message' => 'The server is not compatible with Unix OSSEC rules.'], 422);
        }

        /** @var ?YnhOssecCheck $check */
        $check = YnhOssecCheck::query()
            ->with('policy')
            ->where('uid', $ruleUid)
            ->whereHas('policy', fn($query) => $query->where('uid', self::UNIX_POLICY_UID))
            ->first();

        if (!$check) {
            return response()->json(['message' => 'Unknown Unix OSSEC rule.'], 404);
        }

        return response()->json($check->agentPayload());
    }
}
