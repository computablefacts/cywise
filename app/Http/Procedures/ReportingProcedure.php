<?php

namespace App\Http\Procedures;

use AnourValar\Office\DocumentService;
use AnourValar\Office\Format;
use AnourValar\Office\SheetsService;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Sajya\Server\Procedure;

class ReportingProcedure extends Procedure
{
    public static string $name = 'reporting';

    #[RpcMethod(
        description: "Generate a report for vulnerabilities, open ports, assets (Excel) or a specific vulnerability remediation (Word).",
        params: [
            "report" => "The type of report to create: vulnerabilities, open ports, assets or remediation. (string|min:5|max:15|in:vulnerabilities,ports,assets,remediation)",
            "alert_id" => "The alert id to use for a remediation Word report. (integer|nullable)",
            "vulnerability_name" => "The name of the vulnerability. Extract the core technical identifier or keywords, for example 'uploads/' or '.env' or 'admin.php', rather than a full sentence. (string|nullable)",
            "asset_name" => "The name of the asset or server. (string|nullable)"
        ],
        result: [
            "report" => "A link to the Excel spreadsheet or Word document.",
        ],
        ai_examples: [
            "if the request is 'envoie moi un rapport de vulnérabilités au format Excel', the input should be '{\"report\":\"vulnerabilities\"}'",
            "if the request is 'exporte mes actifs', the input should be '{\"report\":\"assets\"}'",
            "if the request is 'exporte la liste des ports ouverts', the input should be '{\"report\":\"ports\"}'",
            "if the request is 'comment corriger l'alerte 123', the input should be '{\"report\":\"remediation\", \"alert_id\": 123}'",
            "if the request is 'comment corriger la vuln X du serveur Y', the input should be '{\"report\":\"remediation\", \"vulnerability_name\": \"X\", \"asset_name\": \"Y\"}'"
        ],
        ai_result: "{{ \$result['report'] }}"
    )]
    public function create(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'report' => 'string|required|min:5|max:15|in:vulnerabilities,ports,assets,remediation',
            'alert_id' => 'integer|nullable|exists:am_alerts,id',
            'vulnerability_name' => 'string|nullable|max:191',
            'asset_name' => 'string|nullable|max:191',
        ]);
        $data = [];
        $templatePath = '';
        $templateName = '';

        if ($params['report'] === 'vulnerabilities') {
            $data = $this->vulnerabilities($request);
            $templatePath = database_path('seeders/office/vulns-report.xlsx');
            $templateName = 'vulns-report.xlsx';
        } elseif ($params['report'] === 'ports') {
            $data = $this->openPorts($request);
            $templatePath = database_path('seeders/office/ports-report.xlsx');
            $templateName = 'ports-report.xlsx';
        } elseif ($params['report'] === 'assets') {
            $data = $this->assets($request);
            $templatePath = database_path('seeders/office/assets-report.xlsx');
            $templateName = 'assets-report.xlsx';
        } else {
            $alert = $this->remediationAlert(
                $params['alert_id'] ?? null,
                $params['vulnerability_name'] ?? null,
                $params['asset_name'] ?? null,
            );
            $data = $this->remediationData($alert);
            $templatePath = database_path('seeders/office/remediation-report.docx');
            $templateName = 'remediation-report.docx';
        }
        if (!file_exists($templatePath)) {
            throw new \Exception("Template file {$templatePath} not found.");
        }
        if (empty($data['data']) && $params['report'] !== 'remediation') { // see https://github.com/AnourValar/office
            return [
                'report' => 'There is no data to export.',
            ];
        }

        /** @var User $user */
        $user = $request->user();
        $uuid = Str::random(40);
        $report = storage_path("app/private/{$uuid}-{$templateName}");

        if ($params['report'] === 'remediation') {
            (new DocumentService())
                ->generate($templatePath, $data)
                ->saveAs($report, Format::Docx);
        } else {
            (new SheetsService())
                ->generate($templatePath, $data)
                ->saveAs($report, Format::Xlsx);
        }

        $storage = Storage::disk('files-s3');
        $filepath = "/reports";
        $extension = Str::afterLast($templateName, '.');
        $filename = Str::beforeLast($templateName, '.') . "-{$user->id}-{$uuid}.{$extension}";

        if (!$storage->exists($filepath)) {
            if (!$storage->makeDirectory($filepath)) {
                throw new \Exception("Failed to create report");
            }
        }
        if (!$storage->putFileAs($filepath, $report, $filename)) {
            throw new \Exception("Failed to create report.");
        }
        if (file_exists($report)) {
            unlink($report);
        }
        return [
            'report' => app_url() . "/files/download/{$filename}",
        ];
    }

    private function vulnerabilities(JsonRpcRequest $request): array
    {
        $result = (new VulnerabilitiesProcedure())->list(JsonRpcRequest::createFrom($request));
        $high = $result['high'] ?? [];
        $medium = $result['medium'] ?? [];
        $low = $result['low'] ?? [];
        return [
            'data' => collect($high)
                ->concat($medium)
                ->concat($low)
                ->map(fn(Alert $alert) => [
                    'id' => $alert->id,
                    'severity' => $alert->level,
                    'cve' => $alert->cve_id,
                    'asset' => $alert->asset()->asset,
                    'ip' => $alert->port->ip,
                    'port' => $alert->port->port,
                    'service' => $alert->port->service,
                    'product' => $alert->port->product,
                    'vulnerability' => $alert->vulnerability,
                    'remediation' => $alert->remediation,
                ])
                ->values()
                ->toArray(),
        ];
    }

    private function openPorts(JsonRpcRequest $request): array
    {
        $result = (new AssetsProcedure())->list(JsonRpcRequest::createFrom($request));
        $ports = collect($result['assets'] ?? [])->flatMap(fn(array $asset) => Asset::find($asset['uid'])->ports()->get());
        return [
            'data' => $ports
                ->map(fn(Port $port) => [
                    'id' => $port->id,
                    'asset' => $port->scan->asset->asset,
                    'ip' => $port->ip,
                    'port' => $port->port,
                    'protocol' => $port->protocol,
                    'service' => $port->service,
                    'product' => $port->product,
                    'is_open' => $port->closed ? 'No' : 'Yes',
                ])
                ->values()
                ->toArray(),
        ];
    }

    private function assets(JsonRpcRequest $request): array
    {
        $result = (new AssetsProcedure())->list(JsonRpcRequest::createFrom($request));
        $assets = $result['assets'] ?? [];
        return [
            'data' => collect($assets)
                ->map(fn(array $asset) => [
                    'id' => $asset['uid'],
                    'name' => $asset['asset'],
                    'domain' => $asset['tld'],
                    'is_monitored' => $asset['is_monitored'] ? 'Yes' : 'No',
                    'tags' => implode('|', array_map(fn(array $tag) => $tag['name'], $asset['tags'])),
                ])
                ->values()
                ->toArray(),
        ];
    }

    private function remediationAlert(?int $alertId, ?string $vulnerabilityName, ?string $assetName): Alert
    {
        if ($alertId) {
            $alert = $this->visibleAlerts()->first(fn(Alert $alert) => $alert->id === $alertId);
        } else {
            $vulnerabilityName = trim((string) $vulnerabilityName);
            $assetName = trim((string) $assetName);

            if ($vulnerabilityName === '' || $assetName === '') {
                throw new \InvalidArgumentException('The alert_id parameter or both vulnerability_name and asset_name parameters are required for a remediation report.');
            }

            $alert = $this->visibleAlerts()
                ->first(function (Alert $alert) use ($vulnerabilityName, $assetName) {
                    $asset = $alert->asset();

                    return mb_stripos($alert->vulnerability ?? '', $vulnerabilityName) !== false
                        && mb_stripos($asset?->asset ?? '', $assetName) !== false;
                });
        }

        if (! $alert) {
            throw new \Exception('No matching vulnerability alert was found.');
        }

        return $alert;
    }

    private function remediationData(Alert $alert): array
    {
        $asset = $alert->asset();
        $aiRemediation = $this->remediationSections($alert->ai_remediation);

        return [
            'alert_id' => $alert->id,
            'vulnerability' => $this->plainText($alert->vulnerability),
            'remediation' => $this->plainText($alert->remediation),
            'risks' => $aiRemediation['risks'],
            'methodology' => $aiRemediation['methodology'],
            'verification' => $aiRemediation['verification'],
            'asset' => $this->plainText($asset?->asset),
            'ip' => $this->plainText($alert->port?->ip),
            'port' => $this->plainText($alert->port?->port),
        ];
    }

    private function remediationSections(?string $aiRemediation): array
    {
        $fallback = $this->plainText($aiRemediation);

        return [
            'risks' => $this->plainText($this->section($aiRemediation, 'RISQUES') ?? $fallback),
            'methodology' => $this->plainText($this->section($aiRemediation, 'METHODOLOGIE') ?? $fallback),
            'verification' => $this->plainText($this->section($aiRemediation, 'VERIFICATION') ?? 'N/A'),
        ];
    }

    private function section(?string $text, string $section): ?string
    {
        if (!$text) {
            return null;
        }

        $pattern = "/<!--\\s*START_{$section}\\s*-->(.*?)<!--\\s*END_{$section}\\s*-->/is";

        if (preg_match($pattern, $text, $matches) !== 1) {
            return null;
        }

        $section = trim($matches[1]);

        return trim(preg_replace('/^\s*#{1,6}\s+.+?(?:\n{1,2}|$)/u', '', $section));
    }

    private function visibleAlerts(): Collection
    {
        return Asset::query()
            ->where('is_monitored', true)
            ->get()
            ->flatMap(fn(Asset $asset) => $asset->alerts()->get())
            ->filter(fn(Alert $alert) => ! (bool) ($alert->is_hidden ?? false))
            ->values();
    }

    private function plainText(mixed $value): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return 'N/A';
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<!--.*?-->/s', '', $text);
        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $text);
        $text = preg_replace('/<\s*li\b[^>]*>/i', "\n- ", $text);
        $text = preg_replace('/<\s*\/\s*(p|div|li|h[1-6])\s*>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/^[ \t]*```[a-zA-Z0-9_-]*[ \t]*$/m', '', $text);
        $text = preg_replace('/`([^`\n]+)`/', '$1', $text);
        $text = preg_replace('/\[([^\]]+)]\([^)]+\)/', '$1', $text);
        $text = preg_replace('/^[ \t]{0,3}#{1,6}[ \t]+/m', '', $text);
        $text = preg_replace('/(\*\*|__)(.*?)\1/s', '$2', $text);
        $text = preg_replace('/[ \t]+\n/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);

        return trim($text) ?: 'N/A';
    }
}
