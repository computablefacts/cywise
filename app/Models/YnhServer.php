<?php

namespace App\Models;

use App\Enums\OsqueryPlatformEnum;
use App\Enums\ServerStatusEnum;
use App\Enums\SshTraceStateEnum;
use App\Events\CreateAsset;
use App\Hashing\TwHasher;
use App\Helpers\AppStore;
use App\Helpers\SshConnection2;
use App\Helpers\SshKeyPair;
use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string name
 * @property ?string version
 * @property ?string ip_address
 * @property ?int ssh_port
 * @property ?string ssh_username
 * @property ?string ssh_public_key
 * @property ?string ssh_private_key
 * @property ?int created_by
 * @property bool updated
 * @property bool is_ready
 * @property ?int ynh_order_id
 * @property string secret
 * @property string ip_address_v6
 * @property bool is_frozen
 * @property bool added_with_curl
 * @property OsqueryPlatformEnum platform
 */
class YnhServer extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'name',
        'version',
        'ip_address',
        'ip_address_v6',
        'ssh_port',
        'ssh_username',
        'ssh_public_key',
        'ssh_private_key',
        'created_by', // the user who created this server
        'updated', // restricted usage to PullServersInfos
        'is_ready',
        'ynh_order_id',
        'secret',
        'is_frozen',
        'added_with_curl',
        'platform',
    ];

    protected $casts = [
        'updated' => 'boolean',
        'is_ready' => 'boolean',
        'is_frozen' => 'boolean',
        'added_with_curl' => 'boolean',
        'platform' => OsqueryPlatformEnum::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = ['ssh_private_key', 'secret'];

    private ?ServerStatusEnum $statusCached = null;

    public static function expandIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $hex = unpack("H*hex", inet_pton($ip));
            return substr(preg_replace("/([A-f0-9]{4})/", "$1:", $hex['hex']), 0, -1);
        }
        return $ip;
    }

    public static function forUser(User $user, bool $readyOnly = false): Collection
    {
        $currentUser = Auth::user();
        Auth::login($user);

        $query = YnhServer::with('applications', 'domains', 'users')
            ->select('ynh_servers.*')
            ->whereRaw($readyOnly ? "ynh_servers.is_ready = true" : "1=1")
            ->orderBy('ynh_servers.name');

        // dump($query->toSql());

        $servers = $query->get();

        Auth::login($currentUser);

        return $servers;
    }

    public function applications(): HasMany
    {
        return $this
            ->hasMany(YnhApplication::class, 'ynh_server_id', 'id')
            ->whereNotIn('ynh_applications.name', ['sftp', 'ssh']);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(YnhDomain::class, 'ynh_server_id', 'id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(YnhUser::class, 'ynh_server_id', 'id');
    }

    public function traces(): HasMany
    {
        return $this->hasMany(YnhSshTraces::class, 'ynh_server_id', 'id');
    }

    public function backups(): HasMany
    {
        return $this->hasMany(YnhBackup::class, 'ynh_server_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(YnhOrder::class);
    }

    public function isReady(): bool
    {
        return $this->is_ready !== null && $this->is_ready;
    }

    public function isYunoHost(): bool
    {
        return !$this->addedWithCurl() && !$this->isFrozen();
    }

    /** @deprecated */
    public function isFrozen(): bool
    {
        return $this->is_frozen != null && $this->is_frozen;
    }

    /** @deprecated */
    public function addedWithCurl(): bool
    {
        return $this->added_with_curl != null && $this->added_with_curl;
    }

    public function ip(): ?string
    {
        return $this->ip_address;
    }

    public function ipv6(): ?string
    {
        return $this->ip_address_v6 === '<unavailable>' ? null : $this->ip_address_v6;
    }

    public function domain(): ?YnhDomain
    {
        return $this->domains->where('is_principal', true)->first();
    }

    public function lastHeartbeat(): ?Carbon
    {
        $minDate = Carbon::now()->subMinutes(30);
        $heartbeat = YnhOsquery::select(['calendar_time'])
            ->where('ynh_server_id', $this->id)
            ->where('calendar_time', '>=', $minDate->toDateTimeString())
            ->orderBy('calendar_time', 'desc')
            ->first();
        return $heartbeat?->calendar_time;
    }

    public function status(): ServerStatusEnum
    {
        if ($this->isFrozen()) {
            return ServerStatusEnum::UNKNOWN;
        }
        if (!$this->isReady()) {
            return ServerStatusEnum::DOWN;
        }
        if ($this->statusCached) {
            return $this->statusCached;
        }

        $lastHeartbeat = $this->lastHeartbeat();

        if (!$lastHeartbeat) {
            // Here, the server is probably down :-(
            $this->statusCached = ServerStatusEnum::DOWN;
            return $this->statusCached;
        }

        // Check if status is running
        $minDate = Carbon::now()->subMinutes(10);

        if ($lastHeartbeat->isAfter($minDate)) {
            $this->statusCached = ServerStatusEnum::RUNNING;
            return $this->statusCached;
        }

        // Check if status is unknown
        $minDate = $minDate->subMinutes(10);

        if ($lastHeartbeat->isAfter($minDate)) {
            $this->statusCached = ServerStatusEnum::UNKNOWN;
            return $this->statusCached;
        }

        // Here, the server is probably down :-(
        $this->statusCached = ServerStatusEnum::DOWN;
        return $this->statusCached;
    }

    public function sshKeyPair(): SshKeyPair
    {
        $keys = new SshKeyPair();
        $keys->init2($this->ssh_public_key, $this->ssh_private_key);
        return $keys;
    }

    public function currentPermissionsYnh(?YnhUser $user = null): Collection
    {
        if ($user) {
            return YnhPermission::select('ynh_permissions.*')
                ->where('is_user_specific', true)
                ->join('ynh_applications', 'ynh_applications.id', '=', 'ynh_permissions.ynh_application_id')
                ->where('ynh_applications.ynh_server_id', $this->id)
                ->where('ynh_permissions.ynh_user_id', $user->id)
                ->get()
                ->map(fn(YnhPermission $permission) => $permission->name)
                ->unique()
                ->sort();
        }
        return YnhPermission::select('ynh_permissions.*')
            ->join('ynh_applications', 'ynh_applications.id', '=', 'ynh_permissions.ynh_application_id')
            ->where('ynh_applications.ynh_server_id', $this->id)
            ->get()
            ->map(fn(YnhPermission $permission) => $permission->name)
            ->unique()
            ->sort();
    }

    public function availablePermissionsYnh(?YnhUser $user = null): Collection
    {
        if ($user) {
            $available = $this->availablePermissionsYnh();
            $current = $this->currentPermissionsYnh($user);
            return $available->diff($current);
        }
        return DB::table('ynh_applications')
            ->select('ynh_applications.sku', 'ynh_permissions.name AS permission')
            ->leftJoin('ynh_permissions', 'ynh_permissions.ynh_application_id', '=', 'ynh_applications.id')
            ->where('ynh_applications.ynh_server_id', $this->id)
            ->distinct()
            ->get()
            ->flatMap(function ($obj) {
                return AppStore::findPermissionsFromSku($obj->sku)
                    ->map(fn($permission) => (object)[
                        'sku' => $obj->sku,
                        'permission' => $permission,
                    ])
                    ->concat([(object)[
                        'sku' => $obj->sku,
                        'permission' => $obj->permission,
                    ]]);
            })
            ->filter(fn($obj) => $obj->permission !== null)
            ->map(fn($obj) => $obj->permission)
            ->unique()
            ->sort();
    }

    public function currentPermissions(?User $user = null): Collection
    {
        if ($user) {
            return YnhUser::from($user)
                ->flatMap(fn(YnhUser $ynhUser) => $this->currentPermissionsYnh($ynhUser))
                ->unique()
                ->sort();
        }
        return $this->currentPermissionsYnh();
    }

    public function availablePermissions(?User $user = null): Collection
    {
        if ($user) {
            $available = $this->availablePermissions();
            $current = $this->currentPermissions($user);
            return $available->diff($current);
        }
        return $this->availablePermissionsYnh();
    }

    public function pendingActions(): Collection
    {
        $uids = YnhSshTraces::select('uid', DB::raw('count(*) as total'))
            ->where('ynh_server_id', $this->id)
            ->groupBy('uid')
            ->having('total', '=', 1)
            ->get()
            ->map(function ($row) {
                return $row->uid;
            });
        return $this->traces()
            ->whereIn('uid', $uids)
            ->where('ynh_server_id', $this->id)
            ->where('state', SshTraceStateEnum::PENDING->value)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function latestTraces(): Collection
    {
        $trace = $this->traces()
            ->where('ynh_server_id', $this->id)
            ->where('state', '<>', SshTraceStateEnum::PENDING->value)
            ->orderBy('updated_at', 'desc')
            ->limit(1)
            ->first();
        return $trace ? $this->traces()
            ->where('uid', $trace->uid)
            ->where('ynh_server_id', $this->id)
            ->orderBy('order', 'desc')
            ->get() : collect();
    }

    public function sshTestConnection(): bool
    {
        return $this->sshKeyPair()->isSshConnectionUpAndRunning($this->ip(), $this->ssh_port, $this->ssh_username);
    }

    public function sshMonitorServer(SshConnection2 $ssh): bool
    {
        $installScript = YnhOsquery::monitorLinuxServer($this);
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Installing tools for monitoring servers...');
        $filename = 'install-yunohost-' . Str::random(10);
        $isOk = $ssh->upload($filename, $installScript);
        $isOk = $isOk && $ssh->executeScript($filename, true);
        if ($isOk) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Tools for monitoring servers installed.');
        }
        return $isOk;
    }

    public function sshInstallYunoHost(SshConnection2 $ssh, string $domain, string $username): bool
    {
        $password = Str::random(30);
        $installScript = <<<EOT
#!/bin/bash
apt-get install ca-certificates curl jq -y
apt-get remove bind9 --purge --autoremove -y
export SUDO_FORCE_REMOVE=yes

cp /root/.ssh/authorized_keys /root/.ssh/authorized_keys.bak

#### curl https://install.yunohost.org | bash -s -- -a
curl https://install.yunohost.org -o yunohost_install_script
chmod +x yunohost_install_script
./yunohost_install_script -a
rm yunohost_install_script

yunohost tools postinstall --force-diskspace --domain {$domain} --username {$username} --fullname "Towerify Admin" --password "{$password}"
yunohost settings set security.password.passwordless_sudo -v yes
yunohost settings set ssowat.panel_overlay.enabled -v False
yunohost diagnosis run --force
yunohost domain cert install {$domain}
yunohost user permission add ssh.main {$username}
yunohost user permission add sftp.main {$username}

mkdir -p /home/{$username}/.ssh
cp /root/.ssh/authorized_keys.bak /home/{$username}/.ssh/authorized_keys
rm /root/.ssh/authorized_keys.bak
chown {$username}:{$username} /home/{$username}/.ssh/authorized_keys
EOT;

        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Installing YunoHost...');
        $filename = 'install-yunohost-' . Str::random(10);
        $isOk = $ssh->upload($filename, $installScript);
        $isOk = $isOk && $ssh->executeScript($filename);
        if ($isOk) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'YunoHost installed.');
        }
        return $isOk;
    }

    public function sshRestartDocker(SshConnection2 $ssh): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Restarting Docker daemon...');
        if ($ssh->executeCommand("sudo systemctl restart docker", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Docker daemon restarted.');
            return true;
        }
        return false;
    }

    public function sshEnableAdminConsole(SshConnection2 $ssh): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Enabling admin console...');
        if ($ssh->executeCommand("sudo systemctl start yunohost-api && sudo systemctl enable yunohost-api", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Admin console enabled.');
            return true;
        }
        return false;
    }

    public function sshDisableAdminConsole(SshConnection2 $ssh): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Disabling admin console...');
        if ($ssh->executeCommand("sudo systemctl disable yunohost-api && sudo systemctl stop yunohost-api", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Admin console disabled.');
            return true;
        }
        return false;
    }

    // https://yunohost.org/en/backup
    public function sshCreateBackup(SshConnection2 $ssh): array
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Creating backup...');

        if ($ssh->executeCommand("sudo yunohost backup create --json", $output)) {

            foreach ($output as $result) {
                $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, $result);
            }

            $last = trim($output[count($output) - 1]);

            if (Str::startsWith($last, 'Not enough free space') || !(Str::startsWith($last, '{') && Str::endsWith($last, '}'))) {
                $ssh->newTrace(SshTraceStateEnum::ERRORED, 'Backup failed.');
                return [];
            }

            $ssh->newTrace(SshTraceStateEnum::DONE, 'Backup created.');
            return json_decode($last, true);
        }
        return [];
    }

    // Deal with "The following signatures were invalid: EXPKEYSIG XXX DEB.SURY.ORG Automatic Signing Key"
    public function sshUpdateAptCache(SshConnection2 $ssh): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Updating signatures...');
        if ($ssh->executeCommand("sudo apt-key adv --fetch-keys https://packages.sury.org/php/apt.gpg", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Signatures updated.');
            $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Updating packages list...');
            if ($ssh->executeCommand("sudo apt update", $output)) {
                $ssh->newTrace(SshTraceStateEnum::DONE, 'Packages list updated.');
                return true;
            }
        }
        return false;
    }

    public function sshCreateDomain(SshConnection2 $ssh, string $domain): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Creating domain...');
        if ($ssh->executeCommand("sudo yunohost domain add {$domain}", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Domain created.');
            return true;
        }
        return false;
    }

    public function sshRemoveDomain(SshConnection2 $ssh, string $domain): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Removing domain...');
        if ($ssh->executeCommand("sudo yunohost domain remove {$domain} --force", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Domain removed.');
            return true;
        }
        return false;
    }

    public function sshUpdateDnsRecords(SshConnection2 $ssh): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Updating DNS records...');
        if ($ssh->executeCommand("sudo yunohost diagnosis run web dnsrecords --force", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'DNS records updated.');
            return true;
        }
        return false;
    }

    public function sshInstallSslCertificates(SshConnection2 $ssh, string $domain): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Installing SSL certificates...');
        if ($ssh->executeCommand("sudo yunohost domain cert install {$domain} --force", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'SSL certificates installed.');
            return true;
        }
        return false;
    }

    public function sshInstallApp(SshConnection2 $ssh, string $domain, string $sku, string $username, string $password): bool
    {
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Installing app...');
        $script = AppStore::findInstallScriptFromSku($sku);
        if ($script) {
            Log::debug($this->setEnv($domain, $sku, $username, $password, $script));
            $filename = 'install-' . Str::random(10);
            $isOk = $ssh->upload($filename, $this->setEnv($domain, $sku, $username, $password, $script));
            $isOk = $isOk && $ssh->executeScript($filename);
        } else {
            $output = [];
            $isOk = $ssh->executeCommand("sudo yunohost app install {$sku} -a \"domain={$domain}&path=/&admin={$username}&password={$password}\" --force", $output);
        }
        if ($isOk) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'App installed.');
        }
        return $isOk;
    }

    public function sshUninstallApp(SshConnection2 $ssh, string $domain, string $sku, string $username, string $password): bool
    {
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Uninstalling app...');
        $script = AppStore::findUninstallScriptFromSku($sku);
        if ($script) {
            Log::debug($this->setEnv($domain, $sku, $username, $password, $script));
            $filename = 'uninstall-' . Str::random(10);
            $isOk = $ssh->upload($filename, $this->setEnv($domain, $sku, $username, $password, $script));
            $isOk = $isOk && $ssh->executeScript($filename);
        } else {
            $output = [];
            $isOk = $ssh->executeCommand("sudo yunohost app remove {$sku}", $output);
        }
        if ($isOk) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'App uninstalled.');
        }
        return $isOk;
    }

    public function sshCreateOrUpdateUserProfile(SshConnection2 $ssh, string $fullname, string $email, string $username, string $password): bool
    {
        $output = $this->sshListUserInfos($ssh, $username);

        // If the user does not exist, create it
        if (collect($output)->filter(fn($value) => is_string($value))->contains(function (string $value) use ($username) {
            return Str::contains($value, "Unknown user: {$username}");
        })) {
            if (!$this->sshCreateUserProfile($ssh, $fullname, $username, $password)) {
                return false;
            }
        }
        return $this->sshUpdateUserProfile($ssh, $fullname, $username, $password, $email);
    }

    public function sshAddUserPermissionSftp(SshConnection2 $ssh, string $username): bool
    {
        return $this->sshAddUserPermission($ssh, $username, 'sftp.main');
    }

    public function sshAddUserPermissionSsh(SshConnection2 $ssh, string $username): bool
    {
        return $this->sshAddUserPermission($ssh, $username, 'ssh.main');
    }

    public function sshAddUserPermission(SshConnection2 $ssh, string $username, string $permission): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Updating user permissions...');
        if ($ssh->executeCommand("sudo yunohost user permission add {$permission} {$username}", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'User permissions updated.');
            return true;
        }
        return false;
    }

    public function sshRemoveUserPermission(SshConnection2 $ssh, string $username, string $permission): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Removing user permission...');
        if ($ssh->executeCommand("sudo yunohost user permission remove {$permission} {$username}", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'User permission removed.');
            return true;
        }
        return false;
    }

    public function sshReloadFirewall(SshConnection2 $ssh): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Reloading firewall...');
        if ($ssh->executeCommand("sudo yunohost firewall reload", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Firewall reloaded.');
            return true;
        }
        return false;
    }

    public function sshRestartFail2Ban(SshConnection2 $ssh): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Restarting fail2ban...');
        if ($ssh->executeCommand("sudo service fail2ban restart", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Fail2ban restarted.');
            return true;
        }
        return false;
    }

    public function sshDoWhitelistIpAddress(SshConnection2 $ssh, string $ip): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Adding IP address to fail2ban whitelist...');
        if ($ssh->executeCommand("sudo sed -i '/^ignoreip/ { /{$ip}/! s/$/ {$ip}/ }' /etc/fail2ban/jail.conf && sudo systemctl restart fail2ban", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'IP address added to fail2ban whitelist.');
            return true;
        }
        return false;
    }

    public function sshUndoWhitelistIpAddress(SshConnection2 $ssh, string $ip): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Removing IP address from fail2ban whitelist...');
        if ($ssh->executeCommand("sudo sed -i '/^ignoreip/ s/\b{$ip}\b//g' /etc/fail2ban/jail.conf && sudo systemctl restart fail2ban", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'IP address removed from fail2ban whitelist.');
            return true;
        }
        return false;
    }

    public function sshCloseTcpPort(SshConnection2 $ssh, int $port): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Closing TCP port...');
        if ($ssh->executeCommand("sudo yunohost firewall disallow TCP {$port}", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'TCP port closed.');
            return true;
        }
        return false;
    }

    public function sshCloseUdpPort(SshConnection2 $ssh, int $port): bool
    {
        $output = [];
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Closing UDP port...');
        if ($ssh->executeCommand("sudo yunohost firewall disallow UDP {$port}", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'UDP port closed.');
            return true;
        }
        return false;
    }

    public function sshListDiagnosis(SshConnection2 $ssh): array
    {
        return $this->executeSshCommandReturnsJson($ssh, "sudo yunohost diagnosis list --json | jq -c");
    }

    public function sshListApplications(SshConnection2 $ssh): array
    {
        return $this->executeSshCommandReturnsJson($ssh, "sudo yunohost app list --json | jq -c");
    }

    public function sshListUsers(SshConnection2 $ssh): array
    {
        return $this->executeSshCommandReturnsJson($ssh, "sudo yunohost user list --json | jq -c");
    }

    public function sshListDomains(SshConnection2 $ssh): array
    {
        return $this->executeSshCommandReturnsJson($ssh, "sudo yunohost domain list --json | jq -c");
    }

    public function sshListUsersPermissions(SshConnection2 $ssh): array
    {
        return $this->executeSshCommandReturnsJson($ssh, "sudo yunohost user permission list --json | jq -c");
    }

    public function sshConnection(?string $uid, ?User $user): SshConnection2
    {
        return new SshConnection2($this, $uid, $user);
    }

    public function sshGetIpV6(SshConnection2 $ssh): string
    {
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Retrieving server IPV6...');
        $ip = $this->executeSshCommandReturnsCollection($ssh, "ip -6 addr | grep inet6 | awk -F '[ \t]+|/' '{print $3}' | grep -v ^::1 | grep -v ^fe80")
            ->flatMap(fn(string $ip) => Str::of($ip)->split('/\s+/'))
            ->filter(fn(string $ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
            ->first();
        if ($ip) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'IPV6 retrieved.');
            return $ip;
        }
        return '<unavailable>';
    }

    public function addOsqueryEvents(array $events): int
    {
        $rules = YnhOsqueryRule::all();
        $nbEvents = 0;

        foreach ($events as $event) {
            if (!isset($event)) {
                continue;
            }
            try {

                $ruleId = $rules->where('name', $event['name'])->first()?->id;
                $calendarTime = Carbon::createFromFormat('D M j H:i:s Y e', $event['calendarTime'])->setTimezone('UTC');

                /** @var YnhOsquery $e */
                $e = YnhOsquery::firstOrCreate([
                    'ynh_osquery_rule_id' => $ruleId,
                    'ynh_server_id' => $this->id,
                    'name' => $event['name'],
                    'host_identifier' => $event['hostIdentifier'],
                    'calendar_time' => $calendarTime,
                    'unix_time' => $event['unixTime'],
                    'epoch' => $event['epoch'],
                    'counter' => $event['counter'],
                    'numerics' => $event['numerics'],
                    'action' => $event['action'],
                ], [
                    'ynh_osquery_rule_id' => $ruleId,
                    'ynh_server_id' => $this->id,
                    'row' => 0,
                    'name' => $event['name'],
                    'host_identifier' => $event['hostIdentifier'],
                    'calendar_time' => $calendarTime,
                    'unix_time' => $event['unixTime'],
                    'epoch' => $event['epoch'],
                    'counter' => $event['counter'],
                    'numerics' => $event['numerics'],
                    'columns' => $event['columns'],
                    'action' => $event['action'],
                ]);

                /** @var YnhOsqueryLatestEvent $le */
                $le = YnhOsqueryLatestEvent::updateOrCreate([
                    'ynh_server_id' => $this->id,
                    'ynh_osquery_id' => $e->id,
                    'event_name' => $e->name,
                ], [
                    'ynh_server_id' => $this->id,
                    'ynh_osquery_id' => $e->id,
                    'event_name' => $e->name,
                    'calendar_time' => $e->calendar_time,
                    'server_name' => $this->name,
                ]);

                $nbEvents++;

            } catch (\Exception $e) {
                Log::error($e);
                Log::error($event);
            }
        }
        return $nbEvents;
    }

    public function pullServerInfos(?string $uid = null, ?User $user = null): void
    {
        $ssh = $this->sshConnection($uid, $user);

        $ssh->newTrace(SshTraceStateEnum::PENDING, 'Pulling infos from server...');

        $ipv6 = $this->sshGetIpV6($ssh);

        $this->ip_address_v6 = YnhServer::expandIp($ipv6);
        $this->save();

        $apps = $this->sshListApplications($ssh);

        if (isset($apps['apps'])) {
            foreach ($apps['apps'] as $app) {
                YnhApplication::updateOrCreate([
                    'ynh_server_id' => $this->id,
                    'sku' => $app['id'],
                ], [
                    'name' => $app['name'],
                    'description' => $app['description'] ?? null,
                    'version' => $app['version'],
                    'path' => $app['domain_path'] ?? null,
                    'sku' => $app['id'],
                    'ynh_server_id' => $this->id,
                    'updated' => true,
                ]);
            }

            // Add two specific apps, the SSH and the SFTP, that are not packaged apps.
            // Deal with them as if they were.
            YnhApplication::updateOrCreate([
                'ynh_server_id' => $this->id,
                'sku' => 'ssh',
            ], [
                'name' => 'ssh',
                'description' => null,
                'version' => 'shadow',
                'path' => null,
                'sku' => 'ssh',
                'ynh_server_id' => $this->id,
                'updated' => true,
            ]);
            YnhApplication::updateOrCreate([
                'ynh_server_id' => $this->id,
                'sku' => 'sftp',
            ], [
                'name' => 'sftp',
                'description' => null,
                'version' => 'shadow',
                'path' => null,
                'sku' => 'sftp',
                'ynh_server_id' => $this->id,
                'updated' => true,
            ]);

            DB::transaction(function () {
                YnhApplication::where('ynh_server_id', $this->id)
                    ->where('updated', false)
                    ->delete();
                YnhApplication::where('ynh_server_id', $this->id)
                    ->update(['updated' => false]);
            });
        }

        $apps = null;
        $domains = $this->sshListDomains($ssh);

        if (isset($domains['main']) && isset($domains['domains'])) {

            $principal = $domains['main'];

            foreach ($domains['domains'] as $domain) {
                YnhDomain::updateOrCreate([
                    'ynh_server_id' => $this->id,
                    'name' => $domain,
                ], [
                    'name' => $domain,
                    'is_principal' => $domain === $principal,
                    'ynh_server_id' => $this->id,
                    'updated' => true,
                ]);
                CreateAsset::dispatch($this->user()->first(), $domain, true, [$this->name]);
            }
            DB::transaction(function () {
                YnhDomain::where('ynh_server_id', $this->id)
                    ->where('updated', false)
                    ->delete();
                YnhDomain::where('ynh_server_id', $this->id)
                    ->update(['updated' => false]);
            });
        }

        $domains = null;
        $users = $this->sshListUsers($ssh);

        if (isset($users['users'])) {
            foreach ($users['users'] as $username => $user) {
                YnhUser::updateOrCreate([
                    'ynh_server_id' => $this->id,
                    'username' => $user['username'],
                ], [
                    'username' => $user['username'],
                    'fullname' => $user['fullname'],
                    'email' => $user['mail'],
                    'ynh_server_id' => $this->id,
                    'updated' => true,
                ]);
            }
            DB::transaction(function () {
                YnhUser::where('ynh_server_id', $this->id)
                    ->where('updated', false)
                    ->delete();
                YnhUser::where('ynh_server_id', $this->id)
                    ->update(['updated' => false]);
            });
        }

        $permissions = $this->sshListUsersPermissions($ssh);

        if (isset($permissions['permissions'])) {

            $users = $this->users()->get();

            foreach ($permissions['permissions'] as $permission => $scope) {

                $sku = Str::before($permission, '.');
                $app = $this->applications()->where('sku', $sku)->first();

                if ($app && isset($scope['allowed'])) {
                    $users->each(function (YnhUser $user) use ($app, $permission, $scope) {

                        $isVisitors = collect($scope['allowed'])->contains(function (string $scope) {
                            return $scope === 'visitors';
                        });
                        $isAllUsers = collect($scope['allowed'])->contains(function (string $scope) {
                            return $scope === 'all_users';
                        });
                        $isUserSpecific = collect($scope['allowed'])->contains(function (string $scope) use ($user) {
                            return $scope === $user->username;
                        });

                        if ($isVisitors || $isAllUsers || $isUserSpecific) {
                            YnhPermission::updateOrCreate([
                                'ynh_user_id' => $user->id,
                                'ynh_application_id' => $app->id,
                                'name' => $permission,
                            ], [
                                'name' => $permission,
                                'ynh_user_id' => $user->id,
                                'ynh_application_id' => $app->id,
                                'updated' => true,
                                'is_visitors' => $isVisitors,
                                'is_all_users' => $isAllUsers,
                                'is_user_specific' => $isUserSpecific,
                            ]);
                        }
                    });
                }
            }
            DB::transaction(function () use ($app) {
                YnhPermission::where('ynh_permissions.updated', false)
                    ->join('ynh_applications', 'ynh_applications.id', 'ynh_permissions.ynh_application_id')
                    ->where('ynh_applications.ynh_server_id', $this->id)
                    ->delete();
                YnhPermission::join('ynh_applications', 'ynh_applications.id', '=', 'ynh_permissions.ynh_application_id')
                    ->where('ynh_applications.ynh_server_id', $this->id)
                    ->update(['ynh_permissions.updated' => false]);
            });
        }
        $ssh->newTrace(SshTraceStateEnum::DONE, 'Infos pulled from server.');
    }

    protected function sshPrivateKey(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => TwHasher::unhash($value),
            set: fn(string $value) => TwHasher::hash($value),
        );
    }

    private function sshListUserInfos(SshConnection2 $ssh, string $username): array
    {
        return $this->executeSshCommandReturnsJson($ssh, "sudo yunohost user info {$username} --json | jq -c");
    }

    private function sshCreateUserProfile(SshConnection2 $ssh, string $fullname, string $username, string $password): bool
    {
        $output = [];
        $domain = $this->domain();

        if (!$domain) {
            $ssh->newTrace(SshTraceStateEnum::ERRORED, 'Missing principal domain.');
            return false;
        }

        $fullname = preg_replace("/[^A-Za-z0-9 ,.'-]/", '', $fullname);
        $password = Str::replace('!', '\!', $password); // history substitution
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Creating user profile...');
        $isOk = $ssh->executeCommand("sudo yunohost user create {$username} -F \"{$fullname}\" -p \"{$password}\" -d {$domain->name}", $output);
        if (!$isOk) {
            return false;
        }
        if (collect($output)->contains(function (string $value) {
            return Str::contains($value, "This password is among the most used passwords in the world.");
        })) {
            return false;
        }
        $ssh->newTrace(SshTraceStateEnum::DONE, 'User profile created.');
        return true;
    }

    private function sshUpdateUserProfile(SshConnection2 $ssh, string $fullname, string $username, string $password, string $email): bool
    {
        $output = [];
        $fullname = preg_replace("/[^A-Za-z0-9 ,.'-]/", '', $fullname);
        $password = Str::replace('!', '\!', $password); // history substitution
        $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Updating user profile...');
        if ($ssh->executeCommand("sudo yunohost user update {$username} -F \"{$fullname}\" -p \"{$password}\" --add-mailforward {$email}", $output)) {
            $ssh->newTrace(SshTraceStateEnum::DONE, 'User profile updated.');
            return true;
        }
        return false;
    }

    private function executeSshCommandReturnsJson(SshConnection2 $ssh, string $cmd): array
    {
        $output = [];
        if ($ssh->executeCommand($cmd, $output)) {
            $str = trim(collect($output)->join(''));
            try {
                $json = json_decode($str, true);
                // Log::debug($json);
                return $json == null ? [$str] : $json;
            } catch (\Exception $e) {
                Log::error($e);
            }
        }
        Log::warning($output);
        return [];
    }

    private function executeSshCommandReturnsCollection(SshConnection2 $ssh, string $cmd): Collection
    {
        $output = [];
        if ($ssh->executeCommand($cmd, $output)) {
            $str = trim(collect($output)->join(''));
            try {
                return Str::of($str)
                    ->split('/[\n\r]+/')
                    ->map(fn(string $row) => trim($row))
                    ->filter(fn(string $row) => $row && $row !== '');
            } catch (\Exception $e) {
                Log::error($e);
            }
        }
        Log::warning($output);
        return collect();
    }

    private function setEnv(string $domain, string $sku, string $username, string $password, string $script): string
    {
        $script = preg_replace('/{APPS_DOMAIN}/', $domain, $script);
        $script = preg_replace('/{APP_ID}/', $sku, $script);
        $script = preg_replace('/{ADMIN_USERNAME}/', $username, $script);
        $script = preg_replace('/{ADMIN_PASSWORD}/', $password, $script);
        return $script;
    }
}
