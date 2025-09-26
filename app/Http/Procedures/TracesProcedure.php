<?php

namespace App\Http\Procedures;

use App\Models\AppTrace;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class TracesProcedure extends Procedure
{
    public static string $name = 'traces';

    #[RpcMethod(
        description: "List the last n traces.",
        params: [
            'limit' => 'The maximum number of traces to return.'
        ],
        result: [
            "traces" => "An array of traces.",
        ]
    )]
    public function list(Request $request): array
    {
        if (!$request->user()->canManageUsers()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        /** @var int $limit */
        $limit = $params['limit'] ?? 500;

        /** @var User $loggedInUser */
        $loggedInUser = $request->user();

        if ($loggedInUser->isCywiseAdmin()) {
            return [
                "traces" => AppTrace::select([
                    'app_traces.*',
                    DB::raw('users.name AS user_name'),
                    DB::raw('users.email AS user_email'),
                ])
                    ->leftJoin('users', 'users.id', '=', 'app_traces.user_id')
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get(),
            ];
        }

        $users = User::query()
            ->when($loggedInUser->tenant_id, fn($query) => $query->where('tenant_id', '=', $loggedInUser->tenant_id))
            ->when($loggedInUser->customer_id, fn($query) => $query->where('customer_id', '=', $loggedInUser->customer_id))
            ->get();

        return [
            "traces" => AppTrace::select([
                'app_traces.*',
                DB::raw('users.name AS user_name'),
                DB::raw('users.email AS user_email'),
            ])
                ->join('users', 'users.id', '=', 'app_traces.user_id')
                ->whereIn('user_id', $users->pluck('id'))
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(),
        ];
    }
}
