<?php

namespace App\Http\Procedures;

use App\Events\SendAuditReport;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class UsersProcedure extends Procedure
{
    public static string $name = 'users';

    #[RpcMethod(
        description: "Toggle the envoy of the weekly email report to a given user.",
        params: [
            "user_id" => "The user id.",
            "gets_audit_report" => "Whether the user wants to receive the weekly email report (optional)."
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function toggleGetsAuditReport(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'gets_audit_report' => 'nullable|boolean',
        ]);

        /** @var User $loggedInUser */
        $loggedInUser = $request->user();

        /** @var User $user */
        $user = User::query()
            ->where('id', '=', $params['user_id'])
            ->when($loggedInUser->tenant_id, fn($query) => $query->where('tenant_id', '=', $loggedInUser->tenant_id))
            ->when($loggedInUser->customer_id, fn($query) => $query->where('customer_id', '=', $loggedInUser->customer_id))
            ->first();

        if (!$user) {
            throw new \Exception("This user does not belong to your tenant.");
        }

        $user->gets_audit_report = $params['gets_audit_report'] ?? !$user->gets_audit_report;
        $user->save();

        return [
            "msg" => "The user {$user->name} settings have been updated."
        ];
    }

    #[RpcMethod(
        description: "Immediately send the weekly email report to a given user.",
        params: [
            "user_id" => "The user id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function sendAuditReport(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        /** @var User $loggedInUser */
        $loggedInUser = $request->user();

        /** @var User $user */
        $user = User::query()
            ->where('id', '=', $params['user_id'])
            ->when($loggedInUser->tenant_id, fn($query) => $query->where('tenant_id', '=', $loggedInUser->tenant_id))
            ->when($loggedInUser->customer_id, fn($query) => $query->where('customer_id', '=', $loggedInUser->customer_id))
            ->first();

        SendAuditReport::dispatch($user);

        return [
            "msg" => "The email report has been sent to the user {$user->name}."
        ];
    }
}
