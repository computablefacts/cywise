<?php

namespace App\Listeners;

use App\AgentSquad\Assistants\TextAssistant;
use App\Events\EndVulnsScan;
use App\Events\SendAuditReport;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\Scan;
use App\Models\Trial;
use App\Models\User;
use App\Models\YnhOsquery;
use App\Models\YnhServer;
use App\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EndVulnsScanListener extends AbstractListener
{
    private const NO_SCRIPT_TOKEN = '<NO_SCRIPT>';

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
            /** @var Trial $trial */
            $trial = $asset->trial()->first();

            if ($trial) {
                if ($trial->completed) {
                    Log::warning("Trial {$trial->id} is already completed");
                    return;
                }

                /** @var User $user */
                $user = $trial->createdBy;
                $assets = $trial->assets()->get();
                $scansInProgress = $assets->contains(fn(Asset $asset) => $asset->scanInProgress()->isNotEmpty());

                if ($scansInProgress) {
                    Log::warning("Assets are still being scanned for trial {$trial->id}");
                    return;
                }
                if ($user->email !== config('towerify.rapidapi.email')) {
                    SendAuditReport::dispatch($user, true);
                }

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
        $port = $scan->port;
        $port->service = $service;
        $port->product = $product;
        $port->ssl = $ssl ? 1 : 0;
        $port->save();

        $tags = collect($task['tags'] ?? []);
        $tags->each(function (string $label) use ($port) {
            $port->tags()->create(['tag' => Str::lower($label)]);
        });

        Auth::logout();

        $this->setAlerts($port, $task);
        $this->setScreenshot($port, $task);
        $this->markScanAsCompleted($scan);
    }

    private function setAlerts(Port $port, array $task): void
    {
        /** @var Asset $asset */
        $asset = $port->scan->asset;
        /** @var User $user */
        $user = $asset->createdBy;
        $users = User::where('tenant_id', $user->tenant_id)->get();
        $user->actAs(); // Because we need to access the user's prompts through PromptsProcedure

        collect($task['data'] ?? [])
            ->filter(fn(array $data) => isset($data['alerts']) && count($data['alerts']))
            ->flatMap(fn(array $data) => $data['alerts'])
            ->filter(fn(array|string $alert) => is_array($alert))
            ->each(function (array $alert) use ($port, $asset, $users) {
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

                    /** @var Alert $a */
                    $a = Alert::updateOrCreate([
                        'port_id' => $port->id,
                        'uid' => $uid
                    ], [
                        'port_id' => $port->id,
                        'type' => $type,
                        'vulnerability' => $vulnerability,
                        'remediation' => $remediation,
                        'ai_remediation' => $aiRemediation['content'],
                        'false_positive' => $aiRemediation['is_false_positive'],
                        'level' => $level,
                        'uid' => $uid,
                        'cve_id' => $cve_id,
                        'cve_cvss' => $cve_cvss,
                        'cve_vendor' => $cve_vendor,
                        'cve_product' => $cve_product,
                        'title' => $title,
                        'flarum_slug' => null, // TODO : remove?
                    ]);

                    // Cache translations
                    $a->translated('title');
                    $a->translated('vulnerability');
                    $a->translated('remediation');

                    if ($a->isHigh() || $a->isMedium()) {
                        foreach ($users as $u) {
                            if ($asset->asset === $port->ip) {
                                $u->notify(new Notification("{$port->ip}:{$port->port} - {$a->translated('title')} - {$a->translated('vulnerability')}"));
                            } else {
                                $u->notify(new Notification("{$asset->asset} ({$port->ip}:{$port->port}) - {$a->translated('title')} - {$a->translated('vulnerability')}"));
                            }
                        }
                    }
                } catch (\Exception $exception) {
                    Log::error($exception);
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

    private function generateAiRemediation(Port $port, array $alert, string $mode = 'both'): array
    {
        $category = $this->detectVulnerabilityCategory($alert);
        $context = $this->gatherSecurityContext($port, $alert, $category);
        $results = [];
        $isFalsePositive = false;

        if ($mode === 'explanation' || $mode === 'both') {
            $results['explanation'] = $this->processLlmPart($port, $alert, $category, $context, 'explanation', null, $isFalsePositive);
        }
        if (!$isFalsePositive) {
            $isFalsePositive = $this->isFalsePositiveExplanation($results['explanation'] ?? '');
        }
        if ($mode === 'script' || $mode === 'both') {
            if ($isFalsePositive) {
                $results['script'] = self::NO_SCRIPT_TOKEN;
            } else {
                $results['script'] = $this->processLlmPart($port, $alert, $category, $context, 'script', $results['explanation'] ?? null);
            }
        }

        $aiRemediation = $results['explanation'] ?? '';

        if (empty(trim($aiRemediation)) && ($mode === 'explanation' || $mode === 'both')) {
            $aiRemediation = "### " . __('Analyse de la vulnérabilité') . "\n\n" .
                __('Désolé, l\'explication détaillée n\'a pas pu être générée pour cette alerte.') . "\n\n" .
                "**" . __('Détails détectés :') . "**\n" .
                "- **" . __('Vulnerabilité :') . "** " . ($alert['vulnerability'] ?? 'N/A') . "\n" .
                "- **" . __('Solution recommandée :') . "** " . ($alert['remediation'] ?? 'N/A');
        }

        $scriptResult = trim($results['script'] ?? '');

        if ($this->shouldDisplayScript($scriptResult)) {
            $aiRemediation .= "\n\n---\n\n### " . __('Script de Remédiation (BASH)') . "\n\n";
            $aiRemediation .= "> " . __('This script is automatically generated by AI. Please review it carefully before running.') . "\n\n";
            $aiRemediation .= "```bash\n" . $scriptResult . "\n```";
        }
        return [
            'content' => $aiRemediation,
            'is_false_positive' => $isFalsePositive,
        ];
    }

    private function shouldDisplayScript(string $script): bool
    {
        if ($script === '') {
            return false;
        }
        return !Str::contains(Str::lower($script), Str::lower(self::NO_SCRIPT_TOKEN));
    }

    private function isFalsePositiveExplanation(string $explanation): bool
    {
        $normalized = Str::lower($explanation);
        return Str::contains($normalized, '<is_false_positive>true</is_false_positive>')
            || (preg_match('/^\s*true\s*(?:\r?\n|$)/i', $explanation) === 1 && Str::contains($normalized, 'faux positif'));
    }

    private function sanitizeFalsePositiveExplanation(string $explanation): string
    {
        $clean = preg_replace('/<is_false_positive>\s*true\s*<\/is_false_positive>/i', '', $explanation);
        $clean = preg_replace('/^\s*true\s*(\r?\n)+/i', '', $clean ?? '');
        return trim($clean ?? '');
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
        /** @var Asset $asset */
        $asset = $port->scan->asset;
        $assetTags = $asset->tags->pluck('tag')->toArray();
        $portTags = $port->tags->pluck('tag')->toArray();
        $tags = array_unique(array_merge($assetTags, $portTags));
        $technology = 'unknown';

        if (!empty($port->service) && $port->service !== 'unknown') {
            $technology = $port->service;
        }
        if (!empty($port->product) && $port->product !== 'unknown') {
            $technology = $port->product;
        }

        $context = [
            'ip' => $port->ip ?? 'N/A',
            'port' => $port->port ?? 0,
            'protocol' => $port->protocol ?? 'tcp',
            'vulnerability' => $alert['vulnerability'] ?? '',
            'title' => $alert['title'] ?? '',
            'technology' => $technology,
            'cve_id' => $alert['cve_id'] ?? null,
            'tags' => implode(', ', $tags),
        ];

        $server = YnhServer::where('ip_address', $port->ip)
            ->orWhere('ip_address_v6', $port->ip)
            ->first();
        $os = $server ? YnhOsquery::operatingSystem($server->id) : null;
        $context['operating_system'] = $os
            ? "{$os->os} {$os->codename} {$os->major_version}.{$os->minor_version}.{$os->patch_version}"
            : 'unknown';

        if ($category === 'file_exposed') {
            $url = $this->extractExposedUrl($alert, $port);
            if ($url) {
                $context['exposed_url'] = $url;
                $this->fetchExposedContent($url, $context);
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

    private function extractExposedUrl(array $alert, Port $port): ?string
    {
        $url = $alert['url'] ?? $alert['matched_at'] ?? $alert['matched-at'] ?? null;
        $searchIn = ($alert['vulnerability'] ?? '') . ' ' . ($alert['title'] ?? '');

        if (!$url && preg_match('/(?:url|cible|target|host|matched|exposé)\s*:\s*(?:https?:\/\/)?([^\s<>"\']+)/i', $searchIn, $matches)) {
            $url = $matches[1];
        }

        $hasPath = false;

        if ($url) {
            $parsed = parse_url(Str::contains($url, '://') ? $url : 'http://' . $url);
            $hasPath = !empty($parsed['path']) && $parsed['path'] !== '/';
        }
        if (!$hasPath) {
            if (preg_match('/(?:fichier|file|path|chemin|filename)\s*:\s*([^\s<>"\']+)/i', $searchIn, $matches)) {
                $path = ltrim($matches[1], '/');
                $base = $url ?: ($port->ip . ($port->port != 80 && $port->port != 443 ? ':' . $port->port : ''));
                $url = rtrim($base, '/') . '/' . $path;
            }
        }
        if ($url) {
            $url = Str::trim($url);
            if (!Str::contains($url, '://')) {
                $scheme = ($port->ssl || $port->port === 443) ? 'https://' : 'http://';
                $url = $scheme . ltrim($url, '/');
            }
            return $url;
        }
        return null;
    }

    private function fetchExposedContent(string $url, array &$context): void
    {
        try {
            $response = Http::withOptions(['verify' => false])->timeout(10)->get($url);
            if ($response->successful()) {

                $context['file_content'] = Str::limit($response->body(), 4000);
                $serverHeader = Str::lower($response->header('Server', ''));
                $poweredBy = Str::lower($response->header('X-Powered-By', ''));

                if (Str::contains($serverHeader, 'nginx')) {
                    $context['technology'] = 'nginx';
                } elseif (Str::contains($serverHeader, 'apache') || Str::contains($poweredBy, 'apache')) {
                    $context['technology'] = 'apache';
                }
            } else {
                Log::warning("Fetch failed ($url): " . $response->status());
            }
        } catch (\Exception $e) {
            Log::warning("Fetch failed ($url): " . $e->getMessage());
        }
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

    private function processLlmPart(Port $port, array $alert, string $category, array $context, string $type, ?string $explanation = null, ?bool &$detectedFalsePositive = null): string
    {
        $title = $alert['title'] ?? '';
        $alertType = $alert['type'] ?? '';
        $fileContent = $context['file_content'] ?? '';

        if ($category === 'file_exposed' && !empty($fileContent) && $type === 'explanation') {

            $fpResult = TextAssistant::use()
                ->withPrompt('false_positive_prompt', array_merge($context, [
                    'content' => $fileContent,
                    'title' => $title,
                    'type' => $alertType,
                ]))
                ->text();

            if (Str::contains(Str::lower($fpResult), '<is_false_positive>true</is_false_positive>')) {
                $detectedFalsePositive = true;
                return $this->sanitizeFalsePositiveExplanation($fpResult);
            }
        }

        $template = $this->resolveTemplate($category, $type);
        $vars = array_merge($context, [
            'content' => $fileContent,
            'title' => $title,
            'type' => $alertType,
            'remediation' => $alert['remediation'] ?? '',
            'technology_upper' => strtoupper($context['technology']),
            'domain' => $context['ip'],
            'filename' => basename(parse_url($context['exposed_url'] ?? '', PHP_URL_PATH) ?: 'file'),
            'analysis_context' => $explanation ?? '',
            'risky_parts' => !empty($fileContent) ? $fileContent : 'Analyse en cours...',
        ]);

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
                    Log::warning("Script template not found: {$scriptFile}. Returning NO_SCRIPT token.");
                    return self::NO_SCRIPT_TOKEN;
                }
            }
        }

        $timeout = ($type === 'explanation') ? 120 : 60;
        $response = TextAssistant::use()
            ->withTimeout($timeout)
            ->withPrompt($template, $vars)
            ->text();

        if ($type === 'explanation' && $this->isFalsePositiveExplanation($response)) {
            $detectedFalsePositive = true;
            $response = $this->sanitizeFalsePositiveExplanation($response);
        }
        if ($type === 'script' && Str::contains(Str::lower(trim($response)), Str::lower(self::NO_SCRIPT_TOKEN))) {
            return self::NO_SCRIPT_TOKEN;
        }
        return $response;
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
