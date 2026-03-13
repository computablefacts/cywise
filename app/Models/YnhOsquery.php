<?php

namespace App\Models;

use App\Enums\OsqueryPlatformEnum;
use App\Helpers\ScriptProvider;
use App\Http\Procedures\OsqueryRulesProcedure;
use App\Http\Requests\JsonRpcRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int ynh_server_id
 * @property int row
 * @property string name
 * @property string host_identifier
 * @property Carbon calendar_time
 * @property int unix_time
 * @property int epoch
 * @property int counter
 * @property bool numerics
 * @property array columns
 * @property string action
 * @property bool packed
 * @property bool dismissed
 * @property ?int ynh_osquery_rule_id
 */
class YnhOsquery extends Model
{
    use HasFactory;

    protected $table = 'ynh_osquery';

    protected $fillable = [
        'ynh_osquery_rule_id',
        'ynh_server_id',
        'row',
        'name',
        'host_identifier',
        'calendar_time',
        'unix_time',
        'epoch',
        'counter',
        'numerics',
        'columns',
        'action',
        'packed',
        'dismissed',
    ];

    protected $casts = [
        'numerics' => 'boolean',
        'columns' => 'array',
        'calendar_time' => 'datetime',
        'packed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'dismissed' => 'boolean',
    ];

    public static function configLogAlert(YnhServer $server): array
    {
        $url = app_url();
        $path = ($server->platform === OsqueryPlatformEnum::WINDOWS) ? "C:\\Program Files\\osquery\\log\\osqueryd.*.log" : "/var/log/osquery/osqueryd.*.log";
        return ["monitors" => [
            [
                "name" => "Monitor Osquery Daemon Output",
                "path" => $path,
                "match" => ".*",
                "regexp" => true,
                "url" => "{$url}/logalert/{$server->secret}"
            ]
        ],
            "sleep" => 5,
            "echo" => false,
            "verbose" => 1
        ];
    }

    public static function configPerforma(YnhServer $server): array
    {
        return [
            'enabled' => true,
            'host' => $server->user()->first()->performa_domain,
            'secret_key' => $server->user()->first()->performa_secret,
            'group' => '',
            'proto' => 'https:',
            'socket_opts' => [
                'rejectUnauthorized' => false
            ]
        ];
    }

    public static function configOsquery(): array
    {
        $schedule = [];
        (new OsqueryRulesProcedure())
            ->list(new JsonRpcRequest())['rules']
            ->each(function (YnhOsqueryRule $rule) use (&$schedule) {
                $schedule[$rule->name] = [
                    'query' => $rule->query,
                    'interval' => $rule->interval,
                    'removed' => $rule->removed,
                    'snapshot' => $rule->snapshot,
                    'platform' => $rule->platform->value,
                ];
                if ($rule->version) {
                    $schedule[$rule->name]['version'] = $rule->version;
                }
            });
        return [
            "options" => [
                "logger_snapshot_event_type" => "true",
                "schedule_splay_percent" => 10
            ],
            "schedule" => $schedule,
            "file_paths" => [
                "configuration" => [
                    "/etc/passwd",
                    "/etc/shadow",
                    "/etc/ld.so.preload",
                    "/etc/ld.so.conf",
                    "/etc/ld.so.conf.d/%%",
                    "/etc/pam.d/%%",
                    "/etc/resolv.conf",
                    "/etc/rc%/%%",
                    "/etc/my.cnf",
                    "/etc/modules",
                    "/etc/hosts",
                    "/etc/hostname",
                    "/etc/fstab",
                    "/etc/crontab",
                    "/etc/cron%/%%",
                    "/etc/init/%%",
                    "/etc/rsyslog.conf"
                ],
                "binaries" => [
                    "/usr/bin/%%",
                    "/usr/sbin/%%",
                    "/bin/%%",
                    "/sbin/%%",
                    "/usr/local/bin/%%",
                    "/usr/local/sbin/%%"
                ]
            ],
            "events" => [],
            "packs" => [],
        ];
    }

    public static function monitorLinuxServer(YnhServer $server): string
    {
        $url = app_url();
        $whitelist = collect(config('towerify.adversarymeter.ip_addresses'))
            ->map(fn(string $ip) => "sed -i '/^ignoreip/ { /{$ip}/! s/$/ {$ip}/ }' /etc/fail2ban/jail.conf")
            ->join("\n");
        $installPerforma = '';
        $updatePerformaConfig = '';
        if (!is_null($server->user()->first()?->performa_domain)) {
            $installPerforma = ScriptProvider::provide('linux/install-performa.sh');
            $updatePerformaConfig = ScriptProvider::provide('linux/update-performa-config.sh', [
                'url' => $url,
                'secret' => $server->secret,
            ]);
        }
        return ScriptProvider::provide('linux/monitor-server.sh', [
            'url' => $url,
            'secret' => $server->secret,
            'whitelist' => $whitelist,
            'install_performa' => $installPerforma,
            'update_performa_config' => $updatePerformaConfig,
        ]);
    }

    public static function monitorWindowsServer(YnhServer $server): string
    {
        $url = app_url();
        $installPerforma = '';
        $updatePerformaConfig = '';
        $updatePerformaScheduledTask = '';
        if (!is_null($server->user()->first()->performa_domain)) {
            $installPerforma = ScriptProvider::provide('windows/install-performa.ps1', [
                'url' => $url,
            ]);
            $updatePerformaConfig = ScriptProvider::provide('windows/update-performa-config.ps1', [
                'url' => $url,
                'secret' => $server->secret,
            ]);
            $updatePerformaScheduledTask = ScriptProvider::provide('windows/update-performa-scheduled-task.ps1', [
                'name' => $server->name,
            ]);
        }
        return ScriptProvider::provide('windows/monitor-server.ps1', [
            'url' => $url,
            'secret' => $server->secret,
            'install_performa' => $installPerforma,
            'update_performa_config' => $updatePerformaConfig,
            'update_performa_scheduled_task' => $updatePerformaScheduledTask,
        ]);
    }

    public static function monitorLocalMetricsWindows(YnhServer $server): string
    {
        return ScriptProvider::provide('windows/local-metrics.ps1');
    }

    public static function operatingSystem(int $serverId): ?object
    {
        return \Cache::remember('os_infos_' . $serverId, now()->addDays(7), function () use ($serverId) {
            return collect(DB::select("
                SELECT DISTINCT 
                    ynh_osquery.ynh_server_id,
                    ynh_servers.name AS ynh_server_name,
                    TIMESTAMP(ynh_osquery.calendar_time - SECOND(ynh_osquery.calendar_time)) AS `timestamp`,
                    json_unquote(json_extract(ynh_osquery.columns, '$.arch')) AS architecture,
                    json_unquote(json_extract(ynh_osquery.columns, '$.codename')) AS codename,
                    CAST(json_unquote(json_extract(ynh_osquery.columns, '$.major')) AS INTEGER) AS major_version,
                    CAST(json_unquote(json_extract(ynh_osquery.columns, '$.minor')) AS INTEGER) AS minor_version,
                    json_unquote(json_extract(ynh_osquery.columns, '$.platform')) AS os,
                    CASE
                      WHEN json_unquote(json_extract(ynh_osquery.columns, '$.patch')) = 'null' THEN NULL
                      ELSE CAST(json_unquote(json_extract(ynh_osquery.columns, '$.patch')) AS INTEGER)
                    END AS patch_version
                FROM ynh_osquery
                INNER JOIN (
                  SELECT 
                    ynh_server_id, MAX(calendar_time) AS calendar_time 
                  FROM ynh_osquery 
                  WHERE name = 'os_version'
                  GROUP BY ynh_server_id
                ) AS t ON t.ynh_server_id = ynh_osquery.ynh_server_id AND t.calendar_time = ynh_osquery.calendar_time
                INNER JOIN ynh_servers ON ynh_servers.id = ynh_osquery.ynh_server_id
                WHERE ynh_osquery.name = 'os_version'
                AND ynh_osquery.ynh_server_id = {$serverId}
                ORDER BY timestamp DESC
            "))->firstOrFail();
        });
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(YnhOsqueryRule::class, 'ynh_osquery_rule_id', 'id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(YnhServer::class, 'ynh_server_id', 'id');
    }

    public function isSnapshot(): bool
    {
        return $this->action === 'snapshot';
    }

    public function isAdded(): bool
    {
        return $this->action === 'added';
    }

    public function isRemoved(): bool
    {
        return $this->action === 'removed';
    }

    public function logLine(): string
    {
        // Attributes server_name, server_ip_address, comments and score are loaded by EventsProcedure::list
        $criticality = $this->score ?? 0;
        $time = $this->calendar_time->utc()->format('Y-m-d H:i:s');
        $message = $this->message();
        return empty($message) ? '' : "{$time} - {$this->server_name} (ip address: {$this->server_ip_address}) - {$message} (criticality: {$criticality})";
    }

    public function message(): string
    {
        $msg = '';
        if ($this->score > 0) { // IoC
            $msg = $this->comments;
        } else { // Standard security event
            if ($this->name === 'last') {
                $username = $this->columns['username'] === 'null' ? null : $this->columns['username'] ?? null;
                if ($this->isAdded()) {
                    $msg = "L'utilisateur {$username} s'est connecté au serveur.";
                }
                if ($this->isRemoved()) {
                    $msg = "L'utilisateur {$username} s'est déconnecté du serveur.";
                }
            } else if ($this->name === 'shell_history') {
                $username = $this->columns['username'] === 'null' ? null : $this->columns['username'] ?? null;
                if ($this->isAdded()) {
                    $msg = "L'utilisateur {$username} a lancé la commande {$this->columns['command']}.";
                }
            } else if ($this->name === 'users') {
                $username = $this->columns['username'] === 'null' ? null : $this->columns['username'] ?? null;
                if ($this->isAdded()) {
                    $home = empty($this->columns['directory']) ? "" : " ({$this->columns['directory']})";
                    $msg = "L'utilisateur {$username}{$home} a été créé.";
                }
                if ($this->isRemoved()) {
                    $home = empty($this->columns['directory']) ? "" : " ({$this->columns['directory']})";
                    $msg = "L'utilisateur {$username}{$home} a été supprimé.";
                }
            } else if ($this->name === 'groups') {
                if ($this->isAdded()) {
                    $msg = "Le groupe {$this->columns['groupname']} a été créé.";
                }
                if ($this->isRemoved()) {
                    $msg = "Le groupe {$this->columns['groupname']} a été supprimé.";
                }
            } else if ($this->name === 'authorized_keys') {
                if ($this->isAdded()) {
                    $msg = "Une clef SSH a été ajoutée au trousseau {$this->columns['key_file']}.";
                }
                if ($this->isRemoved()) {
                    $msg = "Une clef SSH a été supprimée du trousseau {$this->columns['key_file']}.";
                }
            } else if ($this->name === 'user_ssh_keys') {
                $username = $this->columns['username'] === 'null' ? null : $this->columns['username'] ?? null;
                if ($this->isAdded()) {
                    $msg = "L'utilisateur {$username} a créé une clef SSH ({$this->columns['path']}).";
                }
                if ($this->isRemoved()) {
                    $msg = "L'utilisateur {$username} a supprimé une clef SSH ({$this->columns['path']}).";
                }
            } else if ($this->name === 'etc_hosts') {
                if ($this->isAdded()) {
                    $msg = "L'hôte {$this->columns['hostnames']} redirige vers {$this->columns['address']}.";
                }
                if ($this->isRemoved()) {
                    $msg = "L'hôte {$this->columns['hostnames']} ne redirige plus vers {$this->columns['address']}.";
                }
            } else if ($this->name === 'etc_services') {
                if ($this->isAdded()) {
                    $msg = "Le service {$this->columns['name']} ({$this->columns['comment']}) écoute sur le port {$this->columns['port']} ({$this->columns['protocol']}).";
                }
                if ($this->isRemoved()) {
                    $msg = "Le service {$this->columns['name']} ({$this->columns['comment']}) n'écoute plus sur le port {$this->columns['port']} ({$this->columns['protocol']}).";
                }
            } else if ($this->name === 'interface_addresses') {
                if ($this->isAdded()) {
                    $msg = "L'interface réseau {$this->columns['interface']} ({$this->columns['address']}) a été ajoutée.";
                }
                if ($this->isRemoved()) {
                    $msg = "L'interface réseau {$this->columns['interface']} ({$this->columns['address']}) a été supprimée.";
                }
            } else if ($this->name === 'suid_bin') {
                if ($this->isAdded()) {
                    $msg = "Les privilèges du binaire {$this->columns['path']} ont été élevés.";
                }
                if ($this->isRemoved()) {
                    $msg = "Les privilèges du binaire {$this->columns['path']} ont été abaissés.";
                }
            } else if ($this->name === 'kernel_modules') {
                if ($this->isAdded()) {
                    $msg = "Le module {$this->columns['name']} a été ajouté au noyau.";
                }
                if ($this->isRemoved()) {
                    $msg = "Le module {$this->columns['name']} a été enlevé du noyau.";
                }
            } else if ($this->name === 'processes') {
                if ($this->isAdded()) {
                    $msg = "Le processus {$this->columns['name']} est lancé.";
                }
                if ($this->isRemoved()) {
                    $msg = "Le processus {$this->columns['name']} est arrêté.";
                }
            } else if ($this->name === 'ld_preload_snapshot') {
                if ($this->isSnapshot()) {
                    $msg = "Le binaire {$this->columns['path']} utilise la variable d'environnement LD_PRELOAD={$this->columns['value']}.";
                }
            } else if ($this->name === 'process_listening_port') {
                if ($this->isAdded()) {
                    $msg = "Le processus {$this->columns['path']} écoute à l'adresse {$this->columns['address']}:{$this->columns['port']}.";
                }
                if ($this->isRemoved()) {
                    $msg = "Le processus {$this->columns['path']} n'écoute plus à l'adresse {$this->columns['address']}:{$this->columns['port']}.";
                }
            } else if ($this->name === 'open_sockets') {
                if ($this->isAdded()) {
                    $process = empty($this->columns['path']) ? "{$this->columns['pid']}" : "{$this->columns['path']} ($this->columns['pid'])";
                    $msg = "Le processus {$process} a une connexion ouverte de {$this->columns['local_address']}:{$this->columns['local_port']} vers {$this->columns['remote_address']}:{$this->columns['remote_port']}.";
                }
                if ($this->isRemoved()) {
                    $process = empty($this->columns['path']) ? "{$this->columns['pid']}" : "{$this->columns['path']} ($this->columns['pid'])";
                    $msg = "Le processus {$process} n'a plus de connexion ouverte de {$this->columns['local_address']}:{$this->columns['local_port']} vers {$this->columns['remote_address']}:{$this->columns['remote_port']}.";
                }
            } else if ($this->name === 'startup_items') {
                if ($this->isAdded()) {
                    $msg = "Le service {$this->columns['name']} ({$this->columns['type']}) a été ajouté.";
                }
                if ($this->isRemoved()) {
                    $msg = "Le service {$this->columns['name']} ({$this->columns['type']}) a été supprimé.";
                }
            } else if ($this->name === 'services') {
                if ($this->isAdded()) {
                    $msg = "Le service {$this->columns['name']} ({$this->columns['service_type']}) a été ajouté.";
                }
                if ($this->isRemoved()) {
                    $msg = "Le service {$this->columns['name']} ({$this->columns['service_type']}) a été supprimé.";
                }
            } else if ($this->name === 'crontab') {
                if ($this->isAdded()) {
                    $cron = "{$this->columns['minute']} {$this->columns['hour']} {$this->columns['day_of_month']} {$this->columns['month']} {$this->columns['day_of_week']}";
                    $msg = "La tâche planifiée {$this->columns['command']} ({$cron}) a été ajoutée au fichier {$this->columns['path']}.";
                }
                if ($this->isRemoved()) {
                    $cron = "{$this->columns['minute']} {$this->columns['hour']} {$this->columns['day_of_month']} {$this->columns['month']} {$this->columns['day_of_week']}";
                    $msg = "La tâche planifiée {$this->columns['command']} ({$cron}) a été supprimée du fichier {$this->columns['path']}.";
                }
            } else if ($this->name === 'scheduled_tasks') {
                if ($this->isAdded()) {
                    $schedule = "last_run={$this->columns['last_run_time']}, next_run={$this->columns['next_run_time']}";
                    $msg = "La tâche planifiée {$this->columns['action']} ({$schedule}) a été ajoutée.";
                }
                if ($this->isRemoved()) {
                    $schedule = "last_run={$this->columns['last_run_time']}, next_run={$this->columns['next_run_time']}";
                    $msg = "La tâche planifiée {$this->columns['action']} ({$schedule}) a été supprimée.";
                }
            } else if ($this->name === 'win_packages' ||
                $this->name === 'deb_packages' ||
                $this->name === 'portage_packages' ||
                $this->name === 'npm_packages' ||
                $this->name === 'python_packages' ||
                $this->name === 'rpm_packages' ||
                $this->name === 'homebrew_packages' ||
                $this->name === 'chocolatey_packages') {
                $type = match ($this->name) {
                    'win_packages' => 'win',
                    'deb_packages' => 'deb',
                    'portage_packages' => 'portage',
                    'npm_packages' => 'npm',
                    'python_packages' => 'python',
                    'rpm_packages' => 'rpm',
                    'homebrew_packages' => 'homebrew',
                    'chocolatey_packages' => 'chocolatey',
                    default => 'unknown',
                };
                if ($this->isAdded()) {
                    $os = YnhOsquery::operatingSystem($this->ynh_server_id);
                    if (!$os) {
                        $cves = '';
                    } else {
                        $cves = YnhCve::appCves($os->os, $os->codename, $this->columns['name'], $this->columns['version'])
                            ->pluck('cve')
                            ->unique()
                            ->join(', ');
                    }
                    $warning = empty($cves) ? '' : "Attention, ce paquet est vulnérable: {$cves}.";
                    $msg = "Le paquet {$this->columns['name']} {$this->columns['version']} ({$type}) a été installé. {$warning}";
                }
                if ($this->isRemoved()) {
                    $msg = "Le paquet {$this->columns['name']} {$this->columns['version']} ({$type}) a été désinstallé.";
                }
            } else {
                $msg = isset($this->columns['text']) ? $this->columns['text'] : "Un événement de type {$this->name} est arrivé.";
                $msg = isset($this->columns['text'])
                    ? $this->columns['text']
                    : "Un événement de type {$this->name} est arrivé. Champs disponibles : " . implode(', ', array_map(
                        fn($k, $v) => "$k=$v",
                        array_keys($this->columns),
                        $this->columns
                    ));
            }
        }
        return $msg;
    }
}
