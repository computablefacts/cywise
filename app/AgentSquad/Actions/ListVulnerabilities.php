<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\Helpers\ApiUtilsFacade as ApiUtils2;
use App\Http\Procedures\VulnerabilitiesProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Str;

class ListVulnerabilities extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "list_vulnerabilities",
                "description" => "
Retrieve the list of vulnerabilities associated to one or more assets (domains or IP addresses).
Provide the action to perform followed by the criticality level (high/medium/low/any) and the asset (or all), using the format: 'action:level:asset'.
The action (always list) must come first, followed by a colon and then the criticality level (high/medium/low/any), followed by a colon and then the asset (or all).
For example:
- if the request is 'quelles sont mes vulnérabilités ?', the input should be 'list:any:all'
- if the request is 'quelles sont mes vulnérabilités critiques ?', the input should be 'list:high:all'
- if the request is 'quelles sont les vulnérabilités de www.example.com ?', the input should be 'list:any:www.example.com'
- if the request is 'quelles sont les vulnérabilités de criticité basse de blog.example.com ?', the input should be 'list:low:blog.example.com' 
- if the request is 'quelles sont les vulnérabilités de criticité moyenne du serveur d'adresse IP 192.168.1.1 ?', the input should be 'list:medium:192.168.1.1'                   
                ",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "input" => [
                            "type" => "string",
                            "description" => "The action to perform followed by the criticality level (high/medium/low/any) and the asset (or all), using the format: 'action:level:asset'.",
                        ],
                    ],
                    "required" => ["input"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct()
    {
        //
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        $action = Str::trim(Str::before($input, ':'));
        $level = Str::trim(Str::between($input, ':', ':'));
        $asset = Str::trim(Str::afterLast($input, ':'));

        if ($action !== 'list') {
            return new FailedAnswer(__("Invalid action. Please use list."));
        }
        if (!in_array($level, ['high', 'medium', 'low', 'any'])) {
            return new FailedAnswer(__("Invalid status. Please use high, medium, low, or any."));
        }

        $procedure = new VulnerabilitiesProcedure();
        if ($asset === 'all' && $level === 'any') {
            $request = new JsonRpcRequest();
        } else if ($asset === 'all') {
            $request = new JsonRpcRequest(['level' => $level]);
        } else {
            /** @var Asset $azzet */
            $azzet = Asset::where('asset', $asset)->first();
            if (!$azzet) {
                return new FailedAnswer(__("Asset :asset not found.", ['asset' => $asset]));
            }
            if (!$azzet->is_monitored) {
                return new FailedAnswer(__("Asset :asset is not monitored.", ['asset' => $asset]));
            }
            if ($level === 'any') {
                $request = new JsonRpcRequest(['asset_id' => $azzet->id]);
            } else {
                $request = new JsonRpcRequest(['asset_id' => $azzet->id, 'level' => $level]);
            }
        }
        $request->setUserResolver(fn() => $user);

        try {
            $result = $procedure->list($request);
            $high = collect($result['high'] ?? []);
            $medium = collect($result['medium'] ?? []);
            $low = collect($result['low'] ?? []);

            if ($high->isEmpty() && $medium->isEmpty() && $low->isEmpty()) {
                return new SuccessfulAnswer("No vulnerabilities found.");
            }

            $preamble = "Here are the vulnerabilities associated to your assets in markdown format:";
            $vulnerabilities = $high->concat($medium)->concat($low)->map(function (Alert $alert) {

                if ($alert->isHigh()) {
                    $level = "(criticité haute)";
                } elseif ($alert->isMedium()) {
                    $level = "(criticité moyenne)";
                } elseif ($alert->isLow()) {
                    $level = "(criticité basse)";
                } else {
                    $level = "";
                }
                if (empty($alert->cve_id)) {
                    $cve = "";
                } else {
                    $cve = "**Note.** Cette vulnérabilité a pour identifiant [{$alert->cve_id}](https://nvd.nist.gov/vuln/detail/{$alert->cve_id}).";
                }

                $result = ApiUtils2::translate($alert->vulnerability);

                if ($result['error'] !== false) {
                    $vulnerability = $alert->vulnerability;
                } else {
                    $vulnerability = $result['response'];
                }

                $result = ApiUtils2::translate($alert->remediation);

                if ($result['error'] !== false) {
                    $remediation = $alert->remediation;
                } else {
                    $remediation = $result['response'];
                }
                return "
### {$alert->title} {$level}

**Actif concerné.** L'actif concerné est {$alert->asset()?->asset} pointant vers le serveur 
{$alert->port?->ip}. Le port {$alert->port?->port} de ce serveur est ouvert et expose un service 
{$alert->port?->service} ({$alert->port?->product}).

**Description détaillée.** {$vulnerability}

**Remédiation.** {$remediation}

{$cve}
                ";
            })->join("\n\n");
            return new SuccessfulAnswer("{$preamble}\n\n{$vulnerabilities}");
        } catch (\Exception $e) {
            return new FailedAnswer(__("Action :action failed when applied to criticality level :level and asset :asset:\n\n:msg", ['action' => $action, 'level' => $level, 'asset' => $asset, 'msg' => $e->getMessage()]));
        }
    }
}
