<?php

namespace App\Helpers\Agents;

use App\Models\Alert;
use App\Models\Asset;
use App\Models\User;

/** @deprecated */
class ListVulnerabilities extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "list_vulnerabilities",
                "description" => "Find the user's assets with vulnerabilities.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => [
                            "type" => ["integer", "null"],
                            "description" => "The alert's unique identifier.",
                        ],
                        "asset" => [
                            "type" => ["string", "null"],
                            "description" => "An IP address, a domain or a subdomain.",
                        ],
                        "severity" => [
                            "type" => ["string", "null"],
                            "description" => "The severity levels of the vulnerabilities: High, Medium, or Low.",
                        ],
                    ],
                    "required" => [],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct(User $user, string $threadId, array $messages, array $args = [])
    {
        parent::__construct($user, $threadId, $messages, $args);
    }

    function execute(): AbstractAction
    {
        $id = $this->args['id'] ?? null;
        if ($id === 'null') {
            $id = null;
        }
        $asset = $this->args['asset'] ?? null;
        if ($asset === 'null') {
            $asset = null;
        }
        $severity = $this->args['severity'] ?? null;
        if ($severity === 'null') {
            $severity = null;
        } else if ($severity === 'Critical') {
            $severity = 'High';
        }
        $query = Asset::where('is_monitored', true);

        if (!empty($asset)) {
            $query->where('asset', $asset);
        }

        $this->output = $query->get()
            ->flatMap(fn(Asset $asset) => $asset->alerts()->get())
            ->filter(fn(Alert $alert) => !isset($id) || $alert->id == $id)
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->filter(fn(Alert $alert) => !isset($severity) || $alert->level === $severity)
            ->sortBy(function (Alert $item) {
                if ($item->level === 'High') {
                    return 1;
                }
                if ($item->level === 'Medium') {
                    return 2;
                }
                if ($item->level === 'Low') {
                    return 3;
                }
                return 4;
            });
        return $this;
    }

    public function memoize(): bool
    {
        return false;
    }

    public function html(): string
    {
        $header = "
            <th class='right'>Id</th>
            <th>Actif</th>
            <th>IP</th>
            <th class='right'>Port</th>
            <th>Protocole</th>
            <th>CVE</th>
            <th>Criticité</th>
        ";

        $rows = $this->output
            ->map(function (Alert $alert) {

                $cve = $alert->cve_id ?
                    "<a href='https://nvd.nist.gov/vuln/detail/{$alert->cve_id}' target='_blank'>{$alert->cve_id}</a>" :
                    "n/a";

                if ($alert->level === 'High') {
                    $level = "<span class='lozenge error'>{$alert->level}</span>";
                } else if ($alert->level === 'Medium') {
                    $level = "<span class='lozenge warning'>{$alert->level}</span>";
                } else if ($alert->level === 'Low') {
                    $level = "<span class='lozenge information'>{$alert->level}</span>";
                } else {
                    $level = "<span class='lozenge neutral'>{$alert->level}</span>";
                }
                return "
                    <tr>
                        <td class='right'>{$alert->id}</td>
                        <td>{$alert->asset()?->asset}</td>
                        <td class='right'>{$alert->port()?->ip}</td>
                        <td>{$alert->port()?->port}</td>
                        <td>{$alert->port()?->protocol}</td>
                        <td>{$cve}</td>
                        <td>{$level}</td>
                    </tr>
                ";
            })
            ->join("\n");

        return self::htmlTable($header, $rows, 7);
    }

    public function text(): string
    {
        return $this->markdown();
    }

    public function markdown(): string
    {
        return $this->output->isEmpty() ? 'No vulnerabilities were found.'
            : $this->output->map(function (Alert $alert) {

                $cve = $alert->cve_id ?
                    "https://nvd.nist.gov/vuln/detail/{$alert->cve_id}" :
                    "n/a";
                $vulnerability = addslashes($alert->vulnerability);
                $remediation = addslashes($alert->remediation);

                return "| {$alert->id} | {$alert->asset()?->asset} | {$alert->port()?->ip} | {$alert->port()?->port} | {$alert->port()?->protocol} | {$cve} | {$alert->level} | {$vulnerability} | {$remediation} |";
            })
                ->prepend("| Id | Asset | IP | Port | Protocol | CVE | Severity | Vulnerability | Remediation |")
                ->prepend("|---|---|---|---|---|---|---|---|---|")
                ->join("\n");
    }
}
