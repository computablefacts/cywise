<?php

namespace App\Models;

use App\Enums\OsqueryPlatformEnum;
use App\Enums\ServerStatusEnum;
use App\Enums\SshTraceStateEnum;
use App\Hashing\TwHasher;
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

    /** @deprecated */
    public static function forUser(User $user, bool $readyOnly = false): Collection
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $user->actAs();
        $servers = YnhServer::query()
            ->select('ynh_servers.*')
            ->whereRaw($readyOnly ? "ynh_servers.is_ready = true" : "1=1")
            ->orderBy('ynh_servers.name')
            ->get();
        $currentUser->actAs();
        return $servers;
    }

    public function traces(): HasMany
    {
        return $this->hasMany(YnhSshTraces::class, 'ynh_server_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function isReady(): bool
    {
        return $this->is_ready !== null && $this->is_ready;
    }

    public function isMonitored(): bool
    {
        $ips = collect([$this->ip(), $this->ipv6()])->filter()->unique();
        return $ips->isNotEmpty() && Port::whereIn('ip', $ips)->exists();
    }

    /** @deprecated */
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

    public function sshTestConnection(): bool
    {
        return $this->sshKeyPair()->isSshConnectionUpAndRunning($this->ip(), $this->ssh_port, $this->ssh_username);
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

    protected function sshPrivateKey(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => TwHasher::unhash($value),
            set: fn(string $value) => TwHasher::hash($value),
        );
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
