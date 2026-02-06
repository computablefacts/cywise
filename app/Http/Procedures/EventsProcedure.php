<?php

namespace App\Http\Procedures;

use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\MemosProvider;
use App\AgentSquad\Providers\PromptsProvider;
use App\AgentSquad\Providers\TranslationsProvider;
use App\Http\Requests\JsonRpcRequest;
use App\Models\YnhOsquery;
use App\Models\YnhServer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sajya\Server\Procedure;

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
            'high' => $events->where('score', '>=', 75)->where('score', '<=', 100)->count(),
            'medium' => $events->where('score', '>=', 50)->where('score', '<=', 74)->count(),
            'low' => $events->where('score', '>=', 25)->where('score', '<=', 49)->count(),
        ];
    }

    #[RpcMethod(
        description: "List collected events.",
        params: [
            "min_score" => "A score of 0 indicates a system event; any score above 0 indicates an IoC, with values closer to 100 reflecting a higher probability of compromise. (required|integer|min:0|max:100)",
            "max_score" => "An optional maximum score to filter events by. (nullable|integer|min:0|max:100)",
            "server_id" => "An optional server id to filter events by. (nullable|integer|exists:ynh_servers,id)",
            "window" => "An optional window of time [min_date, max_date] to filter events by."
        ],
        result: [
            "events" => "The list of events over the last 3 days.",
        ],
        ai_examples: [
            "if the request is 'List recent security events', the input should be {\"min_score\":0}",
            "If the request is 'List recent security events excluding indicators of compromise (IoCs)', the input should be {\"max_score\":0}",
            "if the request is 'Show IoCs for server 1', the input should be {\"min_score\":1,\"server_id\":1}",
            "If the request is 'Show suspicious events for server 1', the input should be {\"min_score\":1,\"max_score\":24,\"server_id\":1}",
            "If the request is 'Show low severity events for server 1', the input should be {\"min_score\":25,\"max_score\":49,\"server_id\":1}",
            "If the request is 'Show medium severity events for server 1', the input should be {\"min_score\":50,\"max_score\":74,\"server_id\":1}",
            "If the request is 'Show high severity events for server 1', the input should be {\"min_score\":75,\"server_id\":1}",
        ],
        ai_result: "@json(\$result['events'])"
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'min_score' => 'required|integer|min:0|max:100',
            'max_score' => 'nullable|integer|min:0|max:100',
            'server_id' => 'nullable|integer|exists:ynh_servers,id',
            'window' => 'nullable|array|min:2|max:2',
            'window.*' => 'required|date',
        ]);

        $serverId = $params['server_id'] ?? null;
        $minScore = $params['min_score'] ?? 0;
        $maxScore = $params['max_score'] ?? 100;

        if (isset($params['window'])) {
            $minDate = Carbon::createFromFormat('Y-m-d', $params['window'][0])->startOfDay();
            $maxDate = Carbon::createFromFormat('Y-m-d', $params['window'][1])->endOfDay();
        } else {
            $minDate = Carbon::now()->subDays(2)->startOfDay();
            $maxDate = Carbon::now()->endOfDay();
        }

        // Load servers
        $servers = YnhServer::query()
            ->when($serverId, fn($query, $serverId) => $query->where('id', $serverId))
            ->get();

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

        if ($dismissed->isNotEmpty()) {
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
        description: "Analyze security events and IoCs for a given server to detect suspicious activity.",
        params: [
            "server_id" => "If the IP address is not specified, the server id. (integer|required_without:ip_address|prohibits:ip_address|exists:ynh_servers,id)",
            "ip_address" => "If the server id is not specified, the server IP address. (string|required_without:server_id|prohibits:server_id|min:4|max:15|exists:ynh_servers,ip_address)"
        ],
        result: [
            "activity" => "The activity status: UNKNOWN, NORMAL, SUSPICIOUS, or ANORMAL.",
            "confidence" => "The confidence score between 0 (low) to 1 (high).",
            "reasoning" => "The reasoning behind the analysis.",
            "suggested_action" => "The suggested action to take.",
            "report" => "A full text report in Markdown format.",
        ],
        ai_examples: [
            "if the request is 'Analyze security events for server 1', the input should be {\"server_id\":1}",
            "if the request is 'Is there any suspicious activity on server 163.172.82.3?', the input should be {\"ip_address\":\"163.172.82.3\"}",
        ],
        ai_result: "{{ \$result['report'] }}"
    )]
    public function socOperator(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'server_id' => 'integer|required_without:ip_address|prohibits:ip_address|exists:ynh_servers,id',
            'ip_address' => 'string|required_without:server_id|prohibits:server_id|min:4|max:15|exists:ynh_servers,ip_address'
        ]);

        if (isset($params['server_id'])) {
            $server = YnhServer::where('id', $params['server_id'])->firstOrFail();
        } else {
            $server = YnhServer::where('ip_address', $params['ip_address'])->firstOrFail();
        }

        $user = $request->user();
        $minDate = Carbon::now()->utc()->startOfDay()->subWeek();
        $maxDate = Carbon::now()->utc()->endOfDay();

        Log::debug("Building SOC operator report for server {$server->name} ({$server->ip()})...");

        $eventRequest = new JsonRpcRequest([
            'min_score' => 0, // Load both security events and IoCs
            'server_id' => $server->id,
            'window' => [$minDate->format('Y-m-d'), $maxDate->format('Y-m-d')]
        ]);
        $eventRequest->setUserResolver(fn() => $user);
        $events = $this->list($eventRequest)['events']
            ->map(fn(YnhOsquery $event) => $event->logLine())
            ->filter(fn(string $logLine) => !empty($logLine))
            ->sort() // Reorder events from the oldest to the newest
            ->values();

        if ($events->isEmpty()) {
            Log::debug("No notable events found for server {$server->name} ({$server->ip()})");
            return [
                'server_name' => $server->name,
                'server_ip_address' => $server->ip(),
                'activity' => 'NORMAL',
                'confidence' => 1,
                'reasoning' => "Aucun événement notable n'a été trouvé.",
                'suggested_action' => "Aucune action requise.",
                'report' => "Il n'y a eu aucun événement notable sur le serveur {$server->name} d'adresse IP {$server->ip()} ces derniers jours.",
            ];
        }

        $logs = implode("\n", cywise_compress_log_buffer($events->toArray()));
        $memos = MemosProvider::provide($user, NotesProcedure::SCOPE_IS_SOC_OPERATOR);
        $prompt = PromptsProvider::provide('default_soc_operator', [
            'SERVER_NAME' => $server->name,
            'SERVER_IP_ADDRESS' => $server->ip(),
            'LOGS' => $logs,
            'MEMOS' => $memos,
        ]);
        $answer = LlmsProvider::provide($prompt);

        Log::debug("SOC operator answer for server {$server->name} ({$server->ip()}): " . json_encode([
                "prompt" => $prompt,
                "answer" => $answer,
            ]));

        $matches = null;
        preg_match_all('/(?:```json\s*)?(.*)(?:\s*```)?/s', $answer, $matches);
        $answer = '{' . Str::after(Str::beforeLast(Str::trim($matches[1][0]), '}'), '{') . '}'; //  deal with "}<｜end▁of▁sentence｜>"
        $json = json_decode($answer, true);

        if (empty($json)) {
            Log::error('Failed to parse SOC operator answer (json): ' . $answer);
            return [
                'server_name' => $server->name,
                'server_ip_address' => $server->ip(),
                'activity' => 'UNKNOWN',
                'confidence' => 1,
                'reasoning' => "Unknown.",
                'suggested_action' => "None.",
                'report' => "L'opérateur SOC n'a pas fourni de réponse significative concernant le serveur **{$server->name}** d'adresse IP {$server->ip()} (le JSON est invalide).",
            ];
        }
        if (!isset($json['activity']) || !in_array($json['activity'], ['NORMAL', 'SUSPICIOUS', 'ANORMAL'])) {
            Log::error('Failed to parse SOC operator answer (activity): ' . $answer);
            return [
                'server_name' => $server->name,
                'server_ip_address' => $server->ip(),
                'activity' => 'UNKNOWN',
                'confidence' => 1,
                'reasoning' => "Unknown.",
                'suggested_action' => "None.",
                'report' => "L'opérateur SOC n'a pas fourni de réponse significative concernant le serveur **{$server->name}** d'adresse IP {$server->ip()} (l'attribut 'activity' est invalide).",
            ];
        }
        if (!isset($json['confidence']) || !is_numeric($json['confidence']) || $json['confidence'] < 0 || $json['confidence'] > 1) {
            Log::error('Failed to parse SOC operator answer (confidence): ' . $answer);
            return [
                'server_name' => $server->name,
                'server_ip_address' => $server->ip(),
                'activity' => 'UNKNOWN',
                'confidence' => 1,
                'reasoning' => "Unknown.",
                'suggested_action' => "None.",
                'report' => "L'opérateur SOC n'a pas fourni de réponse significative concernant le serveur **{$server->name}** d'adresse IP {$server->ip()} (l'attribut 'confidence' est invalide).",
            ];
        }
        if (!isset($json['reasoning']) || !is_string($json['reasoning'])) {
            Log::error('Failed to parse SOC operator answer (reasoning): ' . $answer);
            return [
                'server_name' => $server->name,
                'server_ip_address' => $server->ip(),
                'activity' => 'UNKNOWN',
                'confidence' => 1,
                'reasoning' => "Unknown.",
                'suggested_action' => "None.",
                'report' => "L'opérateur SOC n'a pas fourni de réponse significative concernant le serveur **{$server->name}** d'adresse IP {$server->ip()} (l'attribut 'reasoning' est invalide).",
            ];
        }
        if (!isset($json['suggested_action']) || !is_string($json['suggested_action'])) {
            Log::error('Failed to parse SOC operator answer (suggested_action): ' . $answer);
            return [
                'server_name' => $server->name,
                'server_ip_address' => $server->ip(),
                'activity' => 'UNKNOWN',
                'confidence' => 1,
                'reasoning' => "Unknown.",
                'suggested_action' => "None.",
                'report' => "L'opérateur SOC n'a pas fourni de réponse significative concernant le serveur **{$server->name}** d'adresse IP {$server->ip()} (l'attribut 'suggested_action' est invalide).",
            ];
        }

        $reasoning = TranslationsProvider::provide($json['reasoning']);
        $suggestedAction = TranslationsProvider::provide($json['suggested_action']);

        if ($json['activity'] === "NORMAL") {
            return [
                'server_name' => $server->name,
                'server_ip_address' => $server->ip(),
                'activity' => $json['activity'],
                'confidence' => $json['confidence'],
                'reasoning' => $reasoning,
                'suggested_action' => $suggestedAction,
                'report' => "Il n'y a eu aucun événement notable sur le serveur **{$server->name}** d'adresse IP {$server->ip()} ces derniers jours.",
            ];
        }

        $report = "L'activité sur le serveur **{$server->name}** d'adresse IP {$server->ip()} est **{$json['activity']}E**.\n\n";
        $report .= "- **Indice de confiance (0=faible, 1=haute) :** {$json['confidence']}\n";
        $report .= "- **Raisonnement :** {$reasoning}\n";
        $report .= "- **Action suggérée :** {$suggestedAction}";

        return [
            'server_name' => $server->name,
            'server_ip_address' => $server->ip(),
            'activity' => $json['activity'],
            'confidence' => $json['confidence'],
            'reasoning' => $reasoning,
            'suggested_action' => $suggestedAction,
            'report' => $report,
        ];
    }
}