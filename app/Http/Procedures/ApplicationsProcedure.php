<?php

namespace App\Http\Procedures;

use App\Enums\SshTraceStateEnum;
use App\Events\AddTwrUserPermission;
use App\Events\AddUserPermission;
use App\Events\InstallApp;
use App\Events\RemoveUserPermission;
use App\Events\UninstallApp;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use App\Models\YnhApplication;
use App\Models\YnhOrder;
use App\Models\YnhServer;
use App\Models\YnhUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class ApplicationsProcedure extends Procedure
{
    public static string $name = 'applications';

    #[RpcMethod(
        description: "Install an app on a server.",
        params: [
            "order_id" => "The order id.",
            "server_id" => "The server id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function installApp(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var YnhOrder $order */
        $order = YnhOrder::where('id', $params['order_id'])->firstOrFail();

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception("The server configuration is frozen.");
        }
        if (!$server->isReady()) {
            throw new \Exception("The server is not ready yet! Try again in a moment.");
        }

        $domain = $server->domains->where('path', "{$order->sku()}.{$server->domain()->name}")->first();

        if ($domain) {
            throw new \Exception("{$domain} is already in use.");
        }

        $uid = Str::random(10);
        $ssh = $server->sshConnection($uid, Auth::user());
        $ssh->newTrace(SshTraceStateEnum::PENDING, "Your application is being installed!");

        InstallApp::dispatch($uid, Auth::user(), $server, $order);

        return [
            "msg" => "Your application is being installed!"
        ];
    }

    #[RpcMethod(
        description: "Uninstall a previously installed app.",
        params: [
            "application_id" => "The application id.",
            "server_id" => "The server id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function uninstallApp(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'application_id' => 'required|integer|exists:ynh_applications,id',
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var YnhApplication $application */
        $application = YnhApplication::where('id', $params['application_id'])->firstOrFail();

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception("The server configuration is frozen.");
        }

        $uid = Str::random(10);
        $ssh = $server->sshConnection($uid, Auth::user());
        $ssh->newTrace(SshTraceStateEnum::PENDING, "Your application is being removed!");

        UninstallApp::dispatch($uid, Auth::user(), $application);

        return [
            "msg" => "Your application is being removed!"
        ];
    }

    #[RpcMethod(
        description: "Add a permission to the Towerify user.",
        params: [
            "permission" => "The permission to add.",
            "server_id" => "The server id.",
            "user_id" => "The user id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function addTowerifyUserPermission(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'permission' => 'required|string|min:1|max:100',
            'user_id' => 'required|integer|exists:ynh_users,id',
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var User $user */
        $user = User::where('id', $params['user_id'])->firstOrFail();

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception("The server configuration is frozen.");
        }

        // TODO : sanity checks

        $uid = Str::random(10);
        $ssh = $server->sshConnection($uid, Auth::user());
        $ssh->newTrace(SshTraceStateEnum::PENDING, "The user's permission is being added!");

        AddTwrUserPermission::dispatch($uid, Auth::user(), $server, $user, $params['permission']);

        return [
            'msg' => "The user's permission is being added!"
        ];
    }

    #[RpcMethod(
        description: "Add a permission to a given user.",
        params: [
            "permission" => "The permission to add.",
            "server_id" => "The server id.",
            "user_id" => "The user id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function addUserPermission(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'permission' => 'required|string|min:1|max:100',
            'user_id' => 'required|integer|exists:ynh_users,id',
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var YnhUser $user */
        $user = YnhUser::where('id', $params['user_id'])->firstOrFail();

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception("The server configuration is frozen.");
        }

        // TODO : sanity checks

        $uid = Str::random(10);
        $ssh = $server->sshConnection($uid, Auth::user());
        $ssh->newTrace(SshTraceStateEnum::PENDING, "The user's permission is being added!");

        AddUserPermission::dispatch($uid, Auth::user(), $server, $user, $params['permission']);

        return [
            'msg' => "The user's permission is being added!"
        ];
    }

    #[RpcMethod(
        description: "Remove a permission from a given user.",
        params: [
            "permission" => "The permission to remove.",
            "server_id" => "The server id.",
            "user_id" => "The user id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function removeUserPermission(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'permission' => 'required|string|min:1|max:100',
            'user_id' => 'required|integer|exists:ynh_users,id',
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var YnhUser $user */
        $user = YnhUser::where('id', $params['user_id'])->firstOrFail();

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception("The server configuration is frozen.");
        }

        // TODO : sanity checks

        $uid = Str::random(10);
        $ssh = $server->sshConnection($uid, Auth::user());
        $ssh->newTrace(SshTraceStateEnum::PENDING, "The user's permission is being removed!");

        RemoveUserPermission::dispatch($uid, Auth::user(), $server, $user, $params['permission']);

        return [
            'msg' => "The user's permission is being removed!"
        ];
    }
}
