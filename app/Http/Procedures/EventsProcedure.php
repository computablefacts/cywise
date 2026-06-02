<?php

namespace App\Http\Procedures;

use App\AgentSquad\Assistants\ChunkAssistant;
use App\AgentSquad\Assistants\TextAssistant;
use App\AgentSquad\Providers\MemosProvider;
use App\Enums\LanguageEnum;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use App\Models\YnhOsquery;
use App\Models\YnhServer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Parsedown;
use Sajya\Server\Procedure;
use Wave\Page;

class EventsProcedure extends Procedure
{
    public static string $name = 'events';

    #[RpcMethod(
        description: "Compute the number of high, medium and low IoCs for a given user.",
        params: [],
        result: [
            "high" => "The number of IoCs with criticality high.",
            "medium" => "The number of IoCs with criticality medium.",
            "low" => "The number of IoCs with criticality low.",
        ],
    )]
    public function counts(JsonRpcRequest $request): array
    {
        $minDate = Carbon::now()->subDays(2)->startOfDay();
        $maxDate = Carbon::now()->endOfDay();

        // Load servers
        $servers = YnhServer::all();

        // Load events
        $events = YnhOsquery::select([
            DB::raw('ynh_servers.name AS server_name'),
            DB::raw('ynh_servers.ip_address AS server_ip_address'),
            'ynh_osquery_rules.score',
            'ynh_osquery_rules.comments',
            'ynh_osquery.*'
        ])
            ->join('ynh_osquery_rules', 'ynh_osquery_rules.id', '=', 'ynh_osquery.ynh_osquery_rule_id')
            ->join('ynh_servers', 'ynh_servers.id', '=', 'ynh_osquery.ynh_server_id')
            ->where('ynh_osquery.calendar_time', '>=', $minDate)
            ->where('ynh_osquery.calendar_time', '<=', $maxDate)
            ->whereIn('ynh_osquery.ynh_server_id', $servers->pluck('id'))
            ->where('ynh_osquery_rules.enabled', true);

        return [
            'high' => $events->clone()
                ->where('ynh_osquery_rules.score', '>=', 75)
                ->where('ynh_osquery_rules.score', '<=', 100)
                ->count(),
            'medium' => $events->clone()
                ->where('ynh_osquery_rules.score', '>=', 50)
                ->where('ynh_osquery_rules.score', '<=', 74)
                ->count(),
            'low' => $events->clone()
                ->where('ynh_osquery_rules.score', '>=', 25)
                ->where('ynh_osquery_rules.score', '<=', 49)
                ->count(),
        ];
    }

    #[RpcMethod(
        description: "Returns the security events and IoCs collected by the agent deployed on the server. This method does not return any information concerning the asset's external perimeter e.g. vulnerabilities.",
        params: [
            "min_score" => "A score of 0 indicates a system event; any score above 0 indicates an IoC, with values closer to 100 reflecting a higher probability of compromise. (integer|required|min:0|max:100)",
            "max_score" => "An optional maximum score to filter events by. (integer|nullable|min:0|max:100)",
            "rule_name" => "An optional rule name to filter events by. (string|nullable|min:0|max:191)",
            "server_id" => "An optional server id to filter events by.",
            "server_name" => "An optional server name to filter events by. (string|nullable|min:0|max:191|exists:ynh_servers,name)",
            "ip_address" => "An optional server IP address to filter events by. (string|nullable|min:4|max:15|exists:ynh_servers,ip_address)",
            "window" => "An optional window of time [min_date, max_date] to filter events by.",
            "categories" => "An optional list of categories to filter events by."
        ],
        result: [
            "events" => "The list of events over the last 3 days.",
        ],
        ai_examples: [
            "if the request is 'List recent security events', the input should be {\"min_score\":0}",
            "if the request is 'What is the available disk space on 192.168.0.40?', the input should be {\"min_score\":0,\"ip_address\":\"192.168.0.40\"}",
            "If the request is 'List recent security events excluding indicators of compromise (IoCs)', the input should be {\"max_score\":0}",
            "if the request is 'Show IoCs for server 192.168.0.38', the input should be {\"min_score\":1,\"ip_address\":\"192.168.0.38\"}",
            "If the request is 'Show suspicious events for server 192.168.0.39', the input should be {\"min_score\":1,\"max_score\":24,\"ip_address\":\"192.168.0.39\"}",
            "If the request is 'Show low severity events for server 192.168.0.40', the input should be {\"min_score\":25,\"max_score\":49,\"ip_address\":\"192.168.0.40\"}",
            "If the request is 'Show medium severity events for server 192.168.0.41', the input should be {\"min_score\":50,\"max_score\":74,\"ip_address\":\"192.168.0.41\"}",
            "If the request is 'Show high severity events for server 192.168.0.42', the input should be {\"min_score\":75,\"ip_address\":\"192.168.0.42\"}",
            "if the request is 'Was a new SSH authorized key added to a user account?', the input should be {\"min_score\":0,\"rule_name\":\"authorized_keys\"}",
            "if the request is 'Is a `bash` process sending data via POST requests unexpectedly?', the input should be {\"min_score\":0,\"rule_name\":\"bash_exfiltration\"}",
            "if the request is 'Is a shell process (`sh` or `bash`) with open sockets to a remote address indicative of a reverse shell?', the input should be {\"min_score\":0,\"rule_name\":\"behavioral_reverse_shell\"}",
            "if the request is 'Was Busybox installed?', the input should be {\"min_score\":0,\"rule_name\":\"busybox_installed\"}",
            "if the request is 'Is a running `busybox` process expected, or could it be malicious?', the input should be {\"min_score\":0,\"rule_name\":\"busybox_usage\"}",
            "if the request is 'Is Busybox running with `nc` (netcat) in its command line for legitimate purposes?', the input should be {\"min_score\":0,\"rule_name\":\"busybox_netcat_usage\"}",
            "if the request is 'Was a Busybox web server (`busybox httpd`) intentionally started?', the input should be {\"min_score\":0,\"rule_name\":\"busybox_server\"}",
            "if the request is 'Is the `cancel` command-line tool being used for data exfiltration?', the input should be {\"min_score\":0,\"rule_name\":\"cancel_exfiltration\"}",
            "if the request is 'Were new Chocolatey packages installed on a Windows system?', the input should be {\"min_score\":0,\"rule_name\":\"chocolatey_packages\"}",
            "if the request is 'Was a new job added to the crontab?', the input should be {\"min_score\":0,\"rule_name\":\"crontab\"}",
            "if the request is 'Is `curl` being used to send data via POST requests unexpectedly?', the input should be {\"min_score\":0,\"rule_name\":\"curl_exfiltration\"}",
            "if the request is 'Was a file downloaded using `curl`?', the input should be {\"min_score\":0,\"rule_name\":\"curl_file_download\"}",
            "if the request is 'Were new DEB packages installed on a Linux system?', the input should be {\"min_score\":0,\"rule_name\":\"deb_packages\"}",
            "if the request is 'Is the `dig` command being used with `@` for DNS exfiltration?', the input should be {\"min_score\":0,\"rule_name\":\"dns_exfiltration\"}",
            "if the request is 'Was the `dsniff` package installed?', the input should be {\"min_score\":0,\"rule_name\":\"dsniff_installed\"}",
            "if the request is 'Were new entries added to the `/etc/hosts` file?', the input should be {\"min_score\":0,\"rule_name\":\"etc_hosts\"}",
            "if the request is 'Was a new service added to `/etc/services`?', the input should be {\"min_score\":0,\"rule_name\":\"etc_services\"}",
            "if the request is 'Is an FTP process running unexpectedly?', the input should be {\"min_score\":0,\"rule_name\":\"ftp_process\"}",
            "if the request is 'Were new groups added to the system?', the input should be {\"min_score\":0,\"rule_name\":\"groups\"}",
            "if the request is 'Were hidden directories discovered in `/home/` or `/root/`?', the input should be {\"min_score\":0,\"rule_name\":\"hidden_directories\"}",
            "if the request is 'Were hidden files discovered in `/home/` or `/root/`?', the input should be {\"min_score\":0,\"rule_name\":\"hidden_files\"}",
            "if the request is 'Were new Homebrew packages installed on a macOS system?', the input should be {\"min_score\":0,\"rule_name\":\"homebrew_packages\"}",
            "if the request is 'Was the `hping3` package installed?', the input should be {\"min_score\":0,\"rule_name\":\"hping3_installed\"}",
            "if the request is 'Were new network interfaces added?', the input should be {\"min_score\":0,\"rule_name\":\"interface_addresses\"}",
            "if the request is 'Is IP forwarding enabled on a machine?', the input should be {\"min_score\":0,\"rule_name\":\"ip_forwarding\"}",
            "if the request is 'Was a new kernel module loaded?', the input should be {\"min_score\":0,\"rule_name\":\"kernel_modules\"}",
            "if the request is 'Is manual manipulation of kernel modules expected?', the input should be {\"min_score\":0,\"rule_name\":\"kernel_modules_and_extensions\"}",
            "if the request is 'Did an unauthorized user log in via SSH?', the input should be {\"min_score\":0,\"rule_name\":\"last\"}",
            "if the request is 'Is a process running with the `LD_PRELOAD` environment variable set?', the input should be {\"min_score\":0,\"rule_name\":\"ld_preload_snapshot\"}",
            "if the request is 'Was the `nmap` package installed?', the input should be {\"min_score\":0,\"rule_name\":\"nmap_installed\"}",
            "if the request is 'Is the `nmap` process running?', the input should be {\"min_score\":0,\"rule_name\":\"nmap_process\"}",
            "if the request is 'Were new NPM packages installed?', the input should be {\"min_score\":0,\"rule_name\":\"npm_packages\"}",
            "if the request is 'Was the `nbtscan` package installed?', the input should be {\"min_score\":0,\"rule_name\":\"nbtscan_installed\"}",
            "if the request is 'Was the `netcat` package installed?', the input should be {\"min_score\":0,\"rule_name\":\"netcat_installed\"}",
            "if the request is 'Is Netcat listening or executing commands?', the input should be {\"min_score\":0,\"rule_name\":\"netcat_listener\"}",
            "if the request is 'Is the `openssl` command being used with `connect` for data exfiltration?', the input should be {\"min_score\":0,\"rule_name\":\"openssl_exfiltration\"}",
            "if the request is 'Was the operating system version updated?', the input should be {\"min_score\":0,\"rule_name\":\"os_version\"}",
            "if the request is 'Was a PHP server started?', the input should be {\"min_score\":0,\"rule_name\":\"php_server\"}",
            "if the request is 'Were new packages installed via Portage?', the input should be {\"min_score\":0,\"rule_name\":\"portage_packages\"}",
            "if the request is 'Were new Python packages installed?', the input should be {\"min_score\":0,\"rule_name\":\"python_packages\"}",
            "if the request is 'Was a Python HTTP server started?', the input should be {\"min_score\":0,\"rule_name\":\"python_server\"}",
            "if the request is 'Is a RAM disk mounted?', the input should be {\"min_score\":0,\"rule_name\":\"ramdisk\"}",
            "if the request is 'Were new RPM packages installed?', the input should be {\"min_score\":0,\"rule_name\":\"rpm_packages\"}",
            "if the request is 'Was a Ruby HTTP server started?', the input should be {\"min_score\":0,\"rule_name\":\"ruby_server\"}",
            "if the request is 'Was the `scapy` package installed?', the input should be {\"min_score\":0,\"rule_name\":\"scapy_installed\"}",
            "if the request is 'Was a new scheduled task added on a Windows system?', the input should be {\"min_score\":0,\"rule_name\":\"scheduled_tasks\"}",
            "if the request is 'Is `scp` being used for unauthorized file transfers?', the input should be {\"min_score\":0,\"rule_name\":\"scp_secure_copy\"}",
            "if the request is 'Was a new Windows service added?', the input should be {\"min_score\":0,\"rule_name\":\"services\"}",
            "if the request is 'Is a shell process (`sh` or `bash`) with open sockets indicative of a reverse shell?', the input should be {\"min_score\":0,\"rule_name\":\"shell_check\"}",
            "if the request is 'Do new commands in the shell history indicate malicious activity?', the input should be {\"min_score\":0,\"rule_name\":\"shell_history\"}",
            "if the request is 'Was a new user added to the sudoers file?', the input should be {\"min_score\":0,\"rule_name\":\"sudoers\"}",
            "if the request is 'Was a new SUID binary discovered?', the input should be {\"min_score\":0,\"rule_name\":\"suid_bin\"}",
            "if the request is 'Was a new systemd unit added?', the input should be {\"min_score\":0,\"rule_name\":\"systemd\"}",
            "if the request is 'Was a tar archive created?', the input should be {\"min_score\":0,\"rule_name\":\"tar_archive_created\"}",
            "if the request is 'Was the `tcpdump` package installed?', the input should be {\"min_score\":0,\"rule_name\":\"tcpdump_installed\"}",
            "if the request is 'Was a new user account created?', the input should be {\"min_score\":0,\"rule_name\":\"users\"}",
            "if the request is 'Is the `whois` command being used with `-h` for data exfiltration?', the input should be {\"min_score\":0,\"rule_name\":\"whois_exfiltration\"}",
            "if the request is 'Were new programs installed on a Windows system?', the input should be {\"min_score\":0,\"rule_name\":\"win_packages\"}",
            "if the request is 'Was the `wireshark` package installed?', the input should be {\"min_score\":0,\"rule_name\":\"wireshark_installed\"}",
            "if the request is 'Are Netcat (`nc`, `ncat`, or `netcat`) processes running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_netcat_usage\"}",
            "if the request is 'Is the `ettercap` tool running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_ettercap_usage\"}",
            "if the request is 'Is the `nmap` tool scanning the network?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_nmap_usage\"}",
            "if the request is 'Is the `tcpdump` tool capturing network traffic?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_tcpdump_usage\"}",
            "if the request is 'Is the `socat` tool running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_socat_usage\"}",
            "if the request is 'Is the `hping3` tool running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_hping3_usage\"}",
            "if the request is 'Is the `nuclei` tool scanning for vulnerabilities?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_nuclei_usage\"}",
            "if the request is 'Is the `nbtscan` tool scanning the network?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_nbtscan_usage\"}",
            "if the request is 'Is the `mitmv6` tool running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_mitmv6_usage\"}",
            "if the request is 'Is the `responder` tool running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_responder_usage\"}",
            "if the request is 'Is a Bash process using `/dev/tcp` or `/dev/udp` for a reverse shell?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_bash_reverse_shell\"}",
            "if the request is 'Is a Python process using the `socket` module for a reverse shell?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_python_reverse_shell\"}",
            "if the request is 'Is a PHP process using `fsockopen` for a reverse shell?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_php_reverse_shell\"}",
            "if the request is 'Is a Perl process using the `Socket` module for a reverse shell?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_perl_reverse_shell\"}",
            "if the request is 'Is a Ruby process using `TCPSocket` or `exec` for a reverse shell?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_ruby_reverse_shell\"}",
            "if the request is 'Is a Go process using `net.Dial` or `exec.Command` for a reverse shell?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_golang_reverse_shell\"}",
            "if the request is 'Is a PowerShell process making network connections for a reverse shell?', the input should be {\"min_score\":0,\"rule_name\":\"powershell_reverse_shell\"}",
            "if the request is 'Is the `ngrok` tool running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_ngrok_detection\"}",
            "if the request is 'Is the `frp` (Fast Reverse Proxy) tool running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_frp_detection\"}",
            "if the request is 'Is the `lt` (LocalTunnel) tool running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_localtunnel_detection\"}",
            "if the request is 'Is a reverse SSH tunnel (`-R`) being established?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_reverse_ssh_tunnel\"}",
            "if the request is 'Is the `serveo.net` service being used over SSH?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_serveo_detection\"}",
            "if the request is 'Are tools from the `dsniff` suite running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_dsniff_suite_detection\"}",
            "if the request is 'Are offensive security tools (e.g., Metasploit, Cobalt Strike, Mimikatz) running?', the input should be {\"min_score\":0,\"rule_name\":\"cywise_offensive_tools_execution\"}",
        ],
        ai_result: "
@php
\$events = collect(\$result['events'] ?? [])->map(fn(array \$event) => (new \App\Models\YnhOsquery())->forceFill(\$event));
@endphp
@if(\$events->isEmpty())
No security events found.
@else
Below is a list of security events sorted from the most recent to the oldest. The severity of each event is indicated by a score between 0 (system events) and 100 (critical IoCs).
@foreach(\$events as \$event)
- {{ \$event->calendar_time->utc()->format('Y-m-d H:i:s') }} - {{ \$event->server_name }} ({{ \$event->server_ip_address }}) - {{ \$event->message() }} (severity: {{ \$event->score }})
@endforeach
@endif
        "
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'min_score' => 'integer|required|min:0|max:100',
            'max_score' => 'integer|nullable|min:0|max:100',
            'rule_name' => 'string|nullable|min:0|max:191',
            'server_name' => 'string|nullable|min:0|max:191|prohibits:ip_address,server_id|exists:ynh_servers,name',
            'server_id' => 'integer|nullable|prohibits:ip_address,server_name|exists:ynh_servers,id',
            'ip_address' => 'string|nullable|prohibits:server_id,server_name|min:4|max:15|exists:ynh_servers,ip_address',
            'window' => 'array|nullable|min:2|max:2',
            'window.*' => 'date|required',
            'categories' => 'array|nullable',
            'categories.*' => 'string|required',
        ]);

        $minScore = $params['min_score'] ?? 0;
        $maxScore = $params['max_score'] ?? 100;
        $ruleName = $params['rule_name'] ?? null;
        $categories = $params['categories'] ?? null;

        if (isset($params['window'])) {
            $minDate = Carbon::parse($params['window'][0])->startOfDay();
            $maxDate = Carbon::parse($params['window'][1])->endOfDay();
        } else {
            $minDate = Carbon::now()->subDays(2)->startOfDay();
            $maxDate = Carbon::now()->endOfDay();
        }

        // Load servers
        if (isset($params['server_id'])) {
            $servers = YnhServer::where('id', $params['server_id'])->get();
        } else if (isset($params['ip_address'])) {
            $servers = YnhServer::where('ip_address', $params['ip_address'])->get();
        } else if (isset($params['server_name'])) {
            $servers = YnhServer::where('name', $params['server_name'])->get();
        } else {
            $servers = YnhServer::all();
        }

        // Load dismissed markers
        $dismissed = YnhOsquery::select(['ynh_server_id', 'ynh_osquery_rule_id'])
            ->where('dismissed', true)
            ->whereIn('ynh_server_id', $servers->pluck('id'))
            ->distinct()
            ->get();

        // Load events
        $events = YnhOsquery::select([
            DB::raw('ynh_servers.name AS server_name'),
            DB::raw('ynh_servers.ip_address AS server_ip_address'),
            'ynh_osquery_rules.score',
            'ynh_osquery_rules.comments',
            'ynh_osquery_rules.category AS rule_category',
            'ynh_osquery.*'
        ])
            ->join('ynh_osquery_rules', 'ynh_osquery_rules.id', '=', 'ynh_osquery.ynh_osquery_rule_id')
            ->join('ynh_servers', 'ynh_servers.id', '=', 'ynh_osquery.ynh_server_id')
            ->where('ynh_osquery.calendar_time', '>=', $minDate)
            ->where('ynh_osquery.calendar_time', '<=', $maxDate)
            ->whereIn('ynh_osquery.ynh_server_id', $servers->pluck('id'))
            ->where('ynh_osquery_rules.enabled', true)
            ->where('ynh_osquery_rules.score', '>=', $minScore)
            ->where('ynh_osquery_rules.score', '<=', $maxScore);

        if (isset($ruleName)) {
            $events = $events->where('ynh_osquery_rules.name', $ruleName);
        }
        if (isset($categories)) {
            $events = $events->whereIn('ynh_osquery_rules.category', $categories);
        }
        if ($dismissed->filter(fn(object $item) => !is_null($item->ynh_server_id) && !is_null($item->ynh_osquery_rule_id))->isNotEmpty()) {
            $tuples = $dismissed->map(fn(object $item) => "({$item->ynh_server_id}, {$item->ynh_osquery_rule_id})")->implode(',');
            $events = $events->whereRaw("(ynh_server_id, ynh_osquery_rule_id) NOT IN ({$tuples})");
        }
        return [
            'events' => $events->orderBy('calendar_time', 'desc')->get(),
        ];
    }

    #[RpcMethod(
        description: "Dismiss an event (false positive).",
        params: [
            'event_id' => 'The event identifier.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function dismiss(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'event_id' => 'required|integer|exists:ynh_osquery,id',
        ]);

        /** @var YnhOsquery $event */
        $event = YnhOsquery::findOrFail($params['event_id']);
        $event->dismissed = true;
        $event->save();

        return [
            "msg" => "The event has been dismissed!",
        ];
    }

    #[RpcMethod(
        description: "Analyze security events and IoCs collected by the agent deployed on the server to detect suspicious activity. This method does not take into account any information concerning the asset's external perimeter e.g. vulnerabilities.",
        params: [
            "server_id" => "If the IP address is not specified, the server id.",
            "ip_address" => "If the server id is not specified, the server IP address. (string|min:4|max:15|exists:ynh_servers,ip_address)",
            "include_events" => "Whether to include the list of events in the report. Default is true. (boolean|nullable)"
        ],
        result: [
            "activity" => "The activity status: UNKNOWN, NORMAL, SUSPICIOUS, or ANORMAL.",
            "report" => "A full text report in Markdown format.",
        ],
        ai_examples: [
            "if the request is 'Analyze security events for server 163.172.82.2', the input should be {\"ip_address\":\"163.172.82.2\"}",
            "if the request is 'Is there any suspicious activity on server 163.172.82.3?', the input should be {\"ip_address\":\"163.172.82.3\"}",
        ],
        ai_result: "{{ \$result['report'] }}"
    )]
    public function socOperator(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'server_id' => 'integer|required_without:ip_address|prohibits:ip_address|exists:ynh_servers,id',
            'ip_address' => 'string|required_without:server_id|prohibits:server_id|min:4|max:15|exists:ynh_servers,ip_address',
            'include_events' => 'boolean|nullable',
        ]);

        if (isset($params['server_id'])) {
            $server = YnhServer::where('id', $params['server_id'])->firstOrFail();
        } else {
            $server = YnhServer::where('ip_address', $params['ip_address'])->firstOrFail();
        }

        $user = $request->user();
        $day = Carbon::now()->utc();
        $includeEvents = $params['include_events'] ?? true;

        Log::debug("Building SOC operator weekly report for server {$server->name} ({$server->ip()})...");

        Page::where('author_id', $user->id)
            ->whereLike('slug', "weekly-{$day->format('Y-m-d')}-{$server->id}%")
            ->delete();

        // Create weekly report
        $report = $this->analyzeEvents($user, $day, $server);
        $events = $report['events'];
        $activity = $report['activity'];
        $report = $report['report'];

        // Create webpage
        $title = "Rapport sur 7 jours glissants - {$server->name} ({$server->ip()})";
        $slug = "weekly-{$day->format('Y-m-d')}-{$server->id}" . Str::random(64);
        $page = $this->updateOrCreatePage($user, $slug, $title, "{$report}\n\n{$events}");

        Log::debug("Weekly report for server {$server->name} ({$server->ip()}) built : {$page->link()}");

        // Short report
        $report = "{$report}\n\n**<a href=\"{$page->link()}\">Cliquez ici</a>** pour accéder au rapport détaillé.";

        return [
            'server_name' => $server->name,
            'server_ip_address' => $server->ip(),
            'activity' => $activity,
            'report' => $includeEvents ? "{$report}\n\n{$events}" : $report,
        ];
    }

    private function analyzeEvents(User $user, Carbon $day, YnhServer $server): array
    {
        $window = 7; // Most systems have activity patterns that repeat weekly
        $activities = ['NORMAL', 'SUSPICIOUS', 'ANORMAL', 'UNKNOWN'];

        // Load current activity
        $minDate = $day->copy()->subDays($window)->startOfDay();
        $maxDate = $day->copy()->endOfDay();
        $eventRequest = new JsonRpcRequest([
            'min_score' => 0, // Load both security events and IoCs
            'server_id' => $server->id,
            'window' => [$minDate->format('Y-m-d'), $maxDate->format('Y-m-d')]
        ]);

        Log::debug("SOC_OPERATOR - CURRENT_ACTIVITY - Fetching events for server {$server->name} between {$minDate->format('Y-m-d')} and {$maxDate->format('Y-m-d')}");

        $eventRequest->setUserResolver(fn() => $user);
        $events = $this->list($eventRequest)['events']
            ->map(fn(YnhOsquery $event) => $event->logLine())
            ->filter(fn(string $logLine) => !empty($logLine))
            ->sort() // Reorder events from the oldest to the newest
            ->values();
        $eventsMarkdown = "**Evènements ({$events->count()}) :**\n```\nAucun\n```";

        Log::debug("SOC_OPERATOR - CURRENT_ACTIVITY - {$events->count()} events fetched");

        // Load baseline activity (using the same 7-day period two weeks earlier gives you a baseline that is both recent and separated from the current activity)
        $baselineMinDate = $minDate->copy()->subDays(2 * $window);
        $baselineMaxDate = $maxDate->copy()->subDays(2 * $window);

        Log::debug("SOC_OPERATOR - BASELINE_ACTIVITY - Fetching events for server {$server->name} between {$baselineMinDate->format('Y-m-d')} and {$baselineMaxDate->format('Y-m-d')}");

        $eventRequest = new JsonRpcRequest([
            'min_score' => 0, // Load both security events and IoCs
            'server_id' => $server->id,
            'window' => [$baselineMinDate->format('Y-m-d'), $baselineMaxDate->format('Y-m-d')]
        ]);
        $eventRequest->setUserResolver(fn() => $user);
        $eventz = $this->list($eventRequest)['events']
            ->map(fn(YnhOsquery $event) => $event->logLine())
            ->filter(fn(string $logLine) => !empty($logLine))
            ->sort() // Reorder events from the oldest to the newest
            ->values();

        Log::debug("SOC_OPERATOR - BASELINE_ACTIVITY - {$eventz->count()} events fetched");

        if ($events->isEmpty() && $eventz->isEmpty()) {
            return [
                'activity' => 'UNKNOWN',
                'report' => "**Activité :** {$this->activityEnToFr('UNKNOWN')}\n\n**Analyse :** Nous finalisons la baseline pour le serveur {$server->name} d'adresse IP {$server->ip()}.",
                'events' => $eventsMarkdown,
            ];
        }

        // Compress logs lines
        $compressed = cywise_compress_log_buffer($events->toArray(), 0.85);
        $nbCompressed = count($compressed);
        $logs = implode("\n", $compressed);

        Log::debug("SOC_OPERATOR - CURRENT_ACTIVITY - {$events->count()} -> {$nbCompressed} lines after compression");

        $compressed = cywise_compress_log_buffer($eventz->toArray(), 0.85);
        $nbCompressed = count($compressed);
        $baseline = implode("\n", $compressed);

        Log::debug("SOC_OPERATOR - BASELINE_ACTIVITY - {$eventz->count()} -> {$nbCompressed} lines after compression");

        // Load OS information
        $os = YnhOsquery::operatingSystem($server->id);

        // Analyze current activity
        $result = TextAssistant::use()
            ->withPrompt('default_soc_operator', [
                'SERVER_NAME' => $server->name,
                'SERVER_IP_ADDRESS' => $server->ip(),
                'BASELINE' => $baseline,
                'LOGS' => $logs,
                'OS' => isset($os) ? "OS is {$os->os}/{$os->codename}.\nMajor version is {$os->major_version}.\nMinor version is {$os->minor_version}.\nPatch version is {$os->patch_version}." : "OS is unknown.",
                'MEMOS' => MemosProvider::use()
                    ->withScope(NotesProcedure::SCOPE_IS_SOC_OPERATOR)
                    ->withUser($user)
                    ->provide(),
            ])
            ->structured();

        /** @var array $json */
        $json = $result->parsed;
        $nbEvents = count($events->toArray());
        $maxEvents = 100;

        if ($nbEvents > $maxEvents) {
            $oldest = implode("\n", array_slice($events->reverse()->toArray(), 0, $maxEvents)) . "\n...";
        } else {
            $oldest = implode("\n", $events->reverse()->toArray());
        }

        $eventsMarkdown = "**Evènements ({$events->count()}) :**\n```\n{$oldest}\n```";

        if (isset($json['activity'], $json['report']) && in_array($json['activity'], $activities, true)) {
            return [
                'activity' => $json['activity'],
                'report' => "**Activité :** {$this->activityEnToFr($json['activity'])}\n\n**Analyse :** {$this->translateEnToFr($json['report'])}",
                'events' => $eventsMarkdown,
            ];
        }
        return [
            'activity' => 'UNKNOWN',
            'report' => "**Activité :** {$this->activityEnToFr('UNKNOWN')}\n\n**Analyse :** L'opérateur du SOC n'a pas pu évaluer l'activité du serveur.",
            'events' => $eventsMarkdown,
        ];
    }

    private function activityEnToFr(string $activity): string
    {
        return match ($activity) {
            'NORMAL' => 'normale (activité légitime ou attendue sans indicateurs clairs de compromission)',
            'SUSPICIOUS' => 'suspecte (comportement suspect nécessitant une validation ou une surveillance accrue)',
            'ANORMAL' => 'anormale (indicateurs forts de compromission ou comportement clairement malveillant)',
            default => 'inconnue (preuves insuffisantes pour décider de manière fiable)',
        };
    }

    private function translateEnToFr(string $textEn): string
    {
        return ChunkAssistant::use()
            ->withLang(LanguageEnum::ENGLISH)
            ->withChunk($textEn)
            ->translate(LanguageEnum::FRENCH);
    }

    private function updateOrCreatePage(User $user, string $slug, string $title, string $markdown): Page
    {
        return Page::updateOrCreate([
            'author_id' => $user->id,
            'slug' => $slug,
        ], [
            'title' => $title,
            'body' => (new Parsedown)->text($markdown),
            'status' => 'ACTIVE',
        ]);
    }
}