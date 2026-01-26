<?php

namespace App\Listeners;

use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\PromptsProvider;
use App\Events\EndVulnsScan;
use App\Events\SendAuditReport;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\Scan;
use App\Models\User;
use App\Models\YnhTrial;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EndVulnsScanListener extends AbstractListener
{
    public function viaQueue(): string
    {
        return self::MEDIUM;
    }

    protected function handle2($event)
    {
        if (!($event instanceof EndVulnsScan)) {
            throw new \Exception('Invalid event type!');
        }

        $this->handle3($event);

        /** @var Scan $scan */
        $scan = $event->scan();

        if ($scan) {

            /** @var Asset $asset */
            $asset = $scan->asset()->firstOrFail();
            /** @var YnhTrial $trial */
            $trial = $asset->trial()->first();

            if ($trial) {
                if ($trial->completed) {
                    Log::warning("Trial {$trial->id} is already completed");
                    return;
                }

                $user = $trial->createdBy;
                $assets = $trial->assets()->get();
                $scansInProgress = $assets->contains(fn(Asset $asset) => $asset->scanInProgress()->isNotEmpty());

                if ($scansInProgress) {
                    Log::warning("Assets are still being scanned for trial {$trial->id}");
                    return;
                }

                SendAuditReport::dispatch($user, true);

                $trial->completed = true;
                $trial->save();
            }
        }
    }

    private function handle3(EndVulnsScan $event): void
    {
        $scan = $event->scan();
        $dropEvent = $event->drop();
        $taskResult = $event->taskResult;

        if (!$scan) {
            Log::warning("Vulns scan has been removed : {$event->scanId}");
            return;
        }
        if ($scan->vulnsScanHasEnded()) {
            Log::warning("Vulns scan has ended : {$event->scanId}");
            return;
        }
        if (count($taskResult) > 0) {
            $task = $taskResult;
        } else {
            if ($dropEvent) {
                Log::error("Vulns scan event is too old : {$event->scanId}");
                $scan->markAsFailed();
                return;
            }
            if (!$scan->vulnsScanIsRunning()) {
                Log::warning("Vulns scan is not running anymore : {$event->scanId}");
                $scan->markAsFailed();
                return;
            }

            $taskId = $scan->vulns_scan_id;

            try {
                $task = $this->taskOutput($taskId);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $event->sink();
                return;
            }
        }

        $currentTaskName = $task['current_task'] ?? null;
        $currentTaskStatus = $task['current_task_status'] ?? null;
        $service = $task['service'] ?? null;

        if ($service === 'closed') { // The port status (opened) was a false positive
            $port = $scan->port()->first();
            $port->closed = 1;
            $port->save();
            $this->markScanAsCompleted($scan);
            return;
        }
        if ($currentTaskName !== 'alerter' || $currentTaskStatus !== 'DONE') {
            $event->sink();
            return;
        }

        $product = $task['product'] ?? null;
        $ssl = $task['ssl'] ?? null;

        /** @var Port $port */
        $port = $scan->port()->first();
        $port->service = $service;
        $port->product = $product;
        $port->ssl = $ssl ? 1 : 0;
        $port->save();

        $tags = collect($task['tags'] ?? []);
        $tags->each(function (string $label) use ($port) {
            $port->tags()->create(['tag' => Str::lower($label)]);
        });

        $this->setAlertsV1($port, $task);
        $this->setAlertsV2($port, $task);
        $this->setScreenshot($port, $task);
        $this->markScanAsCompleted($scan);
    }

    private function setAlertsV1(Port $port, array $task): void
    {
        collect($task['data'] ?? [])
            ->filter(fn(array $data) => isset($data['tool']) && $data['tool'] === 'alerter' && isset($data['rawOutput']) && $data['rawOutput'])
            ->flatMap(fn(array $data) => collect(preg_split('/\r\n|\r|\n/', $data['rawOutput'])))
            ->filter(fn(string $alert) => $alert !== '')
            ->map(fn(string $alert) => json_decode($alert, true))
            ->filter(fn(?array $alert) => $alert !== null)
            ->each(function (array $alert) use ($port) {
                try {
                    Alert::updateOrCreate([
                        'port_id' => $port->id,
                        'uid' => trim($alert['values'][7])
                    ], [
                        'port_id' => $port->id,
                        'type' => trim($alert['type']),
                        'vulnerability' => trim($alert['values'][4]),
                        'remediation' => trim($alert['values'][5]),
                        'level' => trim($alert['values'][6]),
                        'uid' => trim($alert['values'][7]),
                        'cve_id' => empty($alert['values'][8]) ? null : $alert['values'][8],
                        'cve_cvss' => empty($alert['values'][9]) ? null : $alert['values'][9],
                        'cve_vendor' => empty($alert['values'][10]) ? null : $alert['values'][10],
                        'cve_product' => empty($alert['values'][11]) ? null : $alert['values'][11],
                        'title' => trim($alert['values'][12]),
                        'flarum_slug' => null, // TODO : remove?
                    ]);
                } catch (\Exception $exception) {
                    Log::error($exception);
                    Log::error($alert);
                }
            });
    }

    private function setAlertsV2(Port $port, array $task): void
    {
        /** @var User $user */
        $user = $port->scan->asset->createdBy;
        $user->actAs(); // Because we need to access the user's prompts through PromptsProcedure

        collect($task['data'] ?? [])
            ->filter(fn(array $data) => isset($data['alerts']) && count($data['alerts']))
            ->flatMap(fn(array $data) => $data['alerts'])
            ->filter(fn(array|string $alert) => is_array($alert))
            ->each(function (array $alert) use ($port) {
                try {
                    $type = trim($alert['type']);

                    if (!str_ends_with($type, '_alert')) {
                        $type .= '_v3_alert';
                    }

                    $vulnerability = Str::limit(trim($alert['vulnerability'] ?? ''), 5000);
                    $remediation = Str::limit(trim($alert['remediation'] ?? ''), 5000);
                    $level = trim($alert['level'] ?? '');
                    $uid = trim($alert['uid'] ?? '');
                    $cve_id = empty($alert['cve_id']) ? null : $alert['cve_id'];
                    $cve_cvss = empty($alert['cve_cvss']) ? null : $alert['cve_cvss'];
                    $cve_vendor = empty($alert['cve_vendor']) ? null : $alert['cve_vendor'];
                    $cve_product = empty($alert['cve_product']) ? null : $alert['cve_product'];
                    $title = trim($alert['title'] ?? '');
                    $aiRemediation = $this->generateAiRemediation($port, $alert);

                    Alert::updateOrCreate([
                        'port_id' => $port->id,
                        'uid' => $uid
                    ], [
                        'port_id' => $port->id,
                        'type' => $type,
                        'vulnerability' => $vulnerability,
                        'remediation' => $remediation,
                        'ai_remediation' => $aiRemediation,
                        'level' => $level,
                        'uid' => $uid,
                        'cve_id' => $cve_id,
                        'cve_cvss' => $cve_cvss,
                        'cve_vendor' => $cve_vendor,
                        'cve_product' => $cve_product,
                        'title' => $title,
                        'flarum_slug' => null, // TODO : remove?
                    ]);
                } catch (\Exception $exception) {
                    Log::error($exception);
                    Log::error($alert);
                }
            });
    }

    private function setScreenshot(Port $port, array $task)
    {
        collect($task['data'] ?? [])
            ->filter(fn(array $data) => isset($data['tool']) && $data['tool'] === 'splash' && isset($data['rawOutput']) && $data['rawOutput'])
            ->map(fn(array $data) => json_decode($data['rawOutput'], true))
            ->filter(fn(array $screenshot) => !empty($screenshot['png']))
            ->each(function (array $screenshot) use ($port) {
                try {
                    $port->screenshot()->create([
                        'port_id' => $port->id,
                        'png' => "data:image/png;base64,{$screenshot['png']}",
                    ]);
                } catch (\Exception $exception) {
                    Log::error($exception);
                    Log::error($port);
                }
            });
    }

    private function markScanAsCompleted(Scan $scan): void
    {
        DB::transaction(function () use ($scan) {

            $scan->vulns_scan_ends_at = Carbon::now();
            $scan->save();

            $remaining = Scan::where('asset_id', $scan->asset_id)
                ->where('ports_scan_id', $scan->ports_scan_id)
                ->whereNull('vulns_scan_ends_at')
                ->count();

            if ($remaining === 0) {

                /** @var Asset $asset */
                $asset = $scan->asset()->first();

                if ($asset) {
                    if ($asset->cur_scan_id === $scan->ports_scan_id) {
                        return; // late arrival, ex. when events are processed synchronously
                    }
                    if ($asset->prev_scan_id) {
                        Scan::where('asset_id', $scan->asset_id)
                            ->where('id', $asset->prev_scan_id)
                            ->delete();
                    }

                    $asset->prev_scan_id = $asset->cur_scan_id;
                    $asset->cur_scan_id = $asset->next_scan_id;
                    $asset->next_scan_id = null;
                    $asset->save();
                }
            }
        });
    }

    private function taskOutput(string $taskId): array
    {
        return ApiUtils::task_get_scan_public($taskId);
    }

    private function generateAiRemediation(Port $port, array $alert, string $mode = 'both'): string
    {
        $category = $this->detectVulnerabilityCategory($alert);
        $context = $this->gatherSecurityContext($port, $alert, $category);
        $results = [];

        if ($mode === 'explanation' || $mode === 'both') {
            $results['explanation'] = $this->processLlmPart($port, $alert, $category, $context, 'explanation');
        }
        if ($mode === 'script' || $mode === 'both') {
            $results['script'] = $this->processLlmPart($port, $alert, $category, $context, 'script');
        }

        $aiRemediation = $results['explanation'] ?? '';

        if (!empty($results['script'])) {
            $aiRemediation .= "\n\n---\n\n### " . __('Script de Remédiation (BASH)') . "\n\n";
            $aiRemediation .= "> " . __('This script is automatically generated by AI. Please review it carefully before running.') . "\n\n";
            $aiRemediation .= "```bash\n" . $results['script'] . "\n```";
        }
        return $aiRemediation;
    }

    private function detectVulnerabilityCategory(array $alert): string
    {
        $type = Str::lower($alert['type'] ?? '');
        if (Str::contains($type, ['quickhits_file', 'config_file', 'backup_file', 'file_alert', 'file_v3'])) {
            return 'file_exposed';
        }
        if (Str::contains($type, ['weak_cipher', 'ssl_certificate', 'tls_', 'cipher'])) {
            return "weak_cipher";
        }
        if (!empty($alert['cve_id'])) {
            return "cve";
        }
        return "general";
    }

    private function gatherSecurityContext(Port $port, array $alert, string $category): array
    {
        $context = [
            'ip' => $port->ip ?? 'N/A',
            'port' => $port->port ?? 0,
            'protocol' => $port->protocol ?? 'tcp',
            'vulnerability' => $alert['vulnerability'] ?? '',
            'title' => $alert['title'] ?? '',
            'technology' => 'unknown',
            'cve_id' => $alert['cve_id'] ?? null,
        ];

        if ($category === 'file_exposed') {
            if (preg_match('/url\s*:(?:http?:\/\/)?([^\s<>"\']+)/i', $context['vulnerability'], $matches)) {
                $url = Str::contains($matches[0], 'url :') ? Str::after($matches[0], ':') : $matches[1];
                $url = trim($url);
                $context['exposed_url'] = $url;

                try {
                    $response = Http::withOptions(['verify' => false])->timeout(10)->get($url);
                    if ($response->successful()) {
                        $context['file_content'] = Str::limit($response->body(), 4000);
                        $serverHeader = strtolower($response->header('Server', ''));
                        $poweredBy = strtolower($response->header('X-Powered-By', ''));
                        if (Str::contains($serverHeader, 'nginx')) $context['technology'] = 'nginx';
                        elseif (Str::contains($serverHeader, 'apache') || Str::contains($poweredBy, 'apache')) $context['technology'] = 'apache';
                    }
                } catch (\Exception $e) {
                    Log::warning("Impossible de fetch le fichier exposé: " . $e->getMessage());
                }
            }
        }
        if ($context['technology'] === 'unknown' && in_array($category, ['file_exposed', 'weak_cipher', 'general'])) {
            $context['technology'] = $this->probeTechnology($context['ip'], (int)$context['port']);
        }
        if ($category === 'cve' && $context['cve_id']) {
            $context['cve_info'] = "NIST NVD: https://nvd.nist.gov/vuln/detail/" . strtoupper($context['cve_id']);
        }
        return $context;
    }

    private function probeTechnology(string $ip, int $port): string
    {
        try {
            foreach (["https://$ip:$port", "http://$ip:$port"] as $url) {

                $response = Http::withOptions(['verify' => false])->timeout(3)->head($url);
                $server = strtolower($response->header('Server', ''));

                if (Str::contains($server, 'nginx')) {
                    return 'nginx';
                }
                if (Str::contains($server, 'apache')) {
                    return 'apache';
                }
            }
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
        }
        return 'unknown';
    }

    private function processLlmPart(Port $port, array $alert, string $category, array $context, string $type): string
    {
        $title = $alert['title'] ?? '';
        $alertType = $alert['type'] ?? '';

        if ($category === 'file_exposed' && !empty($context['file_content']) && $type === 'explanation') {
            $fpPrompt = PromptsProvider::provide('false_positive_prompt', [
                'CONTENT' => $context['file_content'],
                'TITLE' => $title,
                'TYPE' => $alertType
            ]);
            $fpResult = LlmsProvider::provide($fpPrompt);
            if (Str::contains(strtolower($fpResult), '<is_false_positive>true</is_false_positive>')) {
                return "Faux positif" . $fpResult;
            }
        }

        $template = $this->resolveTemplate($category, $type);

        $vars = [
            'ip' => $context['ip'],
            'port' => $context['port'],
            'protocol' => $context['protocol'],
            'title' => $title,
            'type' => $alertType,
            'vulnerability' => $context['vulnerability'],
            'remediation' => $alert['remediation'] ?? '',
            'technology' => $context['technology'],
            'technology_upper' => strtoupper($context['technology']),
            'cve_id' => $context['cve_id'] ?? 'N/A',
            'domain' => $context['ip'],
            'filename' => basename(parse_url($context['exposed_url'] ?? '', PHP_URL_PATH) ?: 'file'),
            'analysis_context' => '',
            'risky_parts' => 'Analyse en cours...',
            'cve_info' => $context['cve_info'] ?? '',
        ];

        if ($type === 'script') {

            $scriptDir = base_path("database/seeders/remediations");
            $scriptFile = match ($category) {
                'file_exposed' => "script_{$context['technology']}.bash",
                'weak_cipher' => "fix_weak_ciphers_{$context['technology']}.bash",
                default => null
            };

            if ($scriptFile) {
                if (file_exists("$scriptDir/$scriptFile")) {
                    $vars['script_content'] = file_get_contents("$scriptDir/$scriptFile");
                } else {
                    return "Erreur : Template de script bash introuvable ({$scriptFile}).";
                }
            }
        }
        return LlmsProvider::provide(PromptsProvider::provide($template, $vars));
    }

    private function resolveTemplate(string $category, string $type): string
    {
        $map = [
            'file_exposed' => [
                'explanation' => 'file_removal_explanation_only_prompt',
                'script' => 'file_removal_script_only_prompt',
            ],
            'weak_cipher' => [
                'explanation' => 'weak_cipher_explanation_prompt',
                'script' => 'weak_cipher_script_only_prompt',
            ],
            'cve' => [
                'explanation' => 'cve_explanation_prompt',
                'script' => 'general_script_only_prompt',
            ],
            'general' => [
                'explanation' => 'general_prompt',
                'script' => 'general_script_only_prompt',
            ]
        ];
        return $map[$category][$type] ?? 'explanation_only_prompt';
    }
}
