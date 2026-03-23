<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Sajya\Server\Procedure;

class ReportingProcedure extends Procedure
{
    public static string $name = 'reporting';

    #[RpcMethod(
        description: "Create a report as an Excel spreadsheet.",
        params: [
            "report" => "The type of report to create: vulnerabilities, open ports or assets. (string|min:5|max:15|in:vulnerabilities,ports,assets)",
        ],
        result: [
            "report" => "A link to the Excel spreadsheet.",
        ],
        ai_examples: [
            "if the request is 'envoie moi un rapport de vulnérabilités au format Excel', the input should be '{\"report\":\"vulnerabilities\"}'",
            "if the request is 'exporte mes actifs', the input should be '{\"report\":\"assets\"}'",
            "if the request is 'exporte la liste des ports ouverts', the input should be '{\"report\":\"ports\"}'",
        ],
        ai_result: "{{ \$result['report'] }}"
    )]
    public function create(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'report' => 'string|required|min:5|max:15|in:vulnerabilities,ports,assets',
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
        }
        if (!file_exists($templatePath)) {
            throw new \Exception("Template file {$templatePath} not found.");
        }
        if (empty($data['data'])) { // see https://github.com/AnourValar/office
            return [
                'report' => 'There is no data to export.',
            ];
        }

        /** @var User $user */
        $user = $request->user();
        $report = storage_path("app/private/{$templateName}");

        if (file_exists($report)) {
            unlink($report);
        }

        (new \AnourValar\Office\SheetsService())
            ->generate($templatePath, $data)
            ->saveAs($report, \AnourValar\Office\Format::Xlsx);

        $uuid = Str::random(40);
        $storage = Storage::disk('files-s3');
        $filepath = "/reports";
        $filename = Str::beforeLast($templateName, '.') . "-{$user->id}-{$uuid}.xlsx";

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
        $ports = collect($result['assets'] ?? [])->flatMap(fn(array $asset) => Asset::find($asset['id'])->ports()->get());
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
}
