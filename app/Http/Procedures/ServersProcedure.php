<?php

namespace App\Http\Procedures;

use App\Enums\SshTraceStateEnum;
use App\Events\ConfigureHost;
use App\Events\CreateAsset;
use App\Events\DeleteAsset;
use App\Events\PullServerInfos;
use App\Helpers\SshKeyPair;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use App\Models\YnhDomain;
use App\Models\YnhServer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;
use Symfony\Component\Process\Process;

class ServersProcedure extends Procedure
{
    public static string $name = 'servers';

    #[RpcMethod(
        description: "Create a single server.",
        params: [
            "order_id" => "The order id (optional).",
        ],
        result: [
            "server" => "A server object.",
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'order_id' => 'integer|min:0',
        ]);

        $orderId = $params['order_id'] ?? 0;
        $server = null;

        if ($orderId > 0) {
            $server = YnhServer::where('ynh_order_id', $orderId)->first();
        }
        if (!$server) {

            $keys = new SshKeyPair();
            $keys->init();

            $server = YnhServer::create([
                'name' => '',
                'user_id' => Auth::user()->id,
                'ssh_public_key' => $keys->publicKey(),
                'ssh_private_key' => $keys->privateKey(),
                'ynh_order_id' => $orderId === 0 ? null : $orderId,
                'secret' => Str::random(30),
            ]);

            $server->name = "YNH{$server->id}";
            $server->save();
        }
        return [
            "server" => $server
        ];
    }

    #[RpcMethod(
        description: "Delete a single server and all its associated data.",
        params: [
            "server_id" => "The server id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception("The server configuration is frozen.");
        }

        $uid = Str::random(10);
        $ssh = $server->sshConnection($uid, Auth::user());
        $ssh->newTrace(SshTraceStateEnum::PENDING, "The server is being removed from the inventory!");

        if ($server->ip()) {

            DeleteAsset::dispatch($server->user()->first(), $server->ip());

            $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Stopping asset monitoring...');
            DeleteAsset::dispatch(Auth::user(), $server->ip());
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Asset monitoring stopped.');

            $server->sshEnableAdminConsole($ssh);

            // TODO : remove fail2ban's whitelisted IPs
            // TODO : re-open closed ports
            // See ConfigureHostListener for details
        }

        $server->delete();

        return [
            "msg" => 'The server has been removed from the inventory!'
        ];
    }

    #[RpcMethod(
        description: "Configure the SSH connection of a server.",
        params: [
            "name" => "The server name.",
            "ip" => "The server IP address.",
            "port" => "The server SSH port.",
            "username" => "The server SSH username.",
            "domain" => "The domain pointing to the server.",
            "server_id" => "The server id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function configure(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'name' => 'required|string|min:3|max:30',
            'ip' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|min:1|max:20',
            'domain' => 'required|string|min:1|max:100',
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception("The server configuration is frozen.");
        }
        if ($server->isReady()) {
            throw new \Exception("The server has already been setup. Please, contact the support for more informations.");
        }

        $principal = $server->domain();

        if ($principal && $principal->name !== $params['domain']) {
            throw new \Exception("{$principal->name} is already a principal domain.");
        }

        $domain = trim($params['domain']);
        $process = new Process(['dig', '+short', "*.{$domain}"]);
        $process->run();

        if (!$process->isSuccessful()) {
            $cmd = $process->getCommandLine();
            $output = $process->getErrorOutput();
            Log::error("{$cmd} : {$output}");
            throw new \Exception("The 'dig' command is unavailable.");
        }

        $ip = trim($process->getOutput());

        if (!$ip) {
            $cmd = $process->getCommandLine();
            $output = $process->getOutput();
            Log::error("{$cmd} : {$output}");
            throw new \Exception("The DNS record for {$params['domain']} is not ready yet. Please, wait for DNS propagation and try again.");
        }
        if ($params['ip'] !== $ip) {
            throw new \Exception("IP mismatch: {$params['domain']}'s IP address is {$ip} and not {$params['ip']}.");
        }
        if (!$server->sshKeyPair()->isSshConnectionUpAndRunning($params['ip'], $params['port'], $params['username'])) {
            throw new \Exception("SSH connection failed!");
        }

        $server->name = $params['name'];
        $server->ip_address = $params['ip'];
        $server->ssh_port = $params['port'];
        $server->ssh_username = $params['username'];
        $server->save();

        CreateAsset::dispatch($server->user()->first(), $server->ip(), true, [$server->name]);

        /** @var User $user */
        $user = Auth::user();

        if (!$principal) {
            $server->domains()->save(YnhDomain::updateOrCreate([
                'ynh_server_id' => $server->id,
                'name' => $params['domain'],
            ], [
                'name' => $params['domain'],
                'is_principal' => true,
                'ynh_server_id' => $server->id,
                'updated' => false,
            ]));
            CreateAsset::dispatch($server->user()->first(), $params['domain'], true, [$server->name]);
        }

        $uid = Str::random(10);
        $ssh = $server->sshConnection($uid, $user);
        $ssh->newTrace(SshTraceStateEnum::PENDING, "Your host is being configured!");

        ConfigureHost::dispatch($uid, $user, $server);

        return [
            "msg" => "Your host is being configured!"
        ];
    }

    #[RpcMethod(
        description: "Test if the SSH connection to a server is working.",
        params: [
            "ip" => "The server IP address.",
            "port" => "The server SSH port.",
            "username" => "The server SSH username.",
            "server_id" => "The server id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function testSshConnection(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'ip' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception('The server configuration is frozen.');
        }
        if (!$server->sshKeyPair()->isSshConnectionUpAndRunning($params['ip'], $params['port'], $params['username'])) {
            throw new \Exception('Connection failed!');
        }
        return [
            "msg" => 'Connection succeeded.'
        ];
    }

    #[RpcMethod(
        description: "Execute a shell command on a server.",
        params: [
            "cmd" => "The shell command to execute.",
            "server_id" => "The server id.",
        ],
        result: [
            "output" => "The command output.",
        ]
    )]
    public function executeShellCommand(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'cmd' => 'required|string|max:800',
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception("The server configuration is frozen.");
        }

        $cmd = $params['cmd'];
        $uid = Str::random(10);
        $ssh = $server->sshConnection($uid, Auth::user());
        $ssh->newTrace(SshTraceStateEnum::PENDING, "Executing shell command...");

        $output = [];

        if ($ssh->executeCommand($cmd, $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, "Shell command executed.");
            $str = trim(collect($output)->join(''));
            return [
                "output" => Str::of($str)
                    ->split('/[\n\r]+/')
                    ->map(fn(string $row) => trim($row))
                    ->filter(fn(string $row) => $row && $row !== ''),
            ];
        }
        throw new \Exception('An error occurred');
    }

    #[RpcMethod(
        description: "Pull the server's information.",
        params: [
            "server_id" => "The server id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function pullServerInfos(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        /** @var YnhServer $server */
        $server = YnhServer::where('id', $params['server_id'])->firstOrFail();

        if ($server->isFrozen()) {
            throw new \Exception("The server configuration is frozen.");
        }

        $uid = Str::random(10);
        $ssh = $server->sshConnection($uid, Auth::user());
        $ssh->newTrace(SshTraceStateEnum::PENDING, "The server infos are being pulled!");

        PullServerInfos::dispatch($uid, Auth::user(), $server);

        return [
            "msg" => "The server infos are being pulled!"
        ];
    }

    #[RpcMethod(
        description: "Retrieve the security events for a specific server over the past 5 days.",
        params: [
            "server_id" => "The server id.",
        ],
        result: [
            "events" => "An array of security events.",
        ]
    )]
    public function messages(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'server_id' => 'required|integer|exists:ynh_servers,id',
        ]);

        $minDate = Carbon::now()->utc()->startOfDay()->subDays(5);
        $maxDate = Carbon::now()->utc()->endOfDay();
        $request = JsonRpcRequest::createFrom($request);
        $request->merge([
            'min_score' => 0,
            'window' => [$minDate->format('Y-m-d'), $maxDate->format('Y-m-d')],
        ]);

        return (new EventsProcedure())->list($request);
    }
}
