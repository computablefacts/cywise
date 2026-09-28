<?php

namespace App\Listeners;

use App\Events\EndVulnsScan;
use App\Events\GenerateAiRemediation;
use App\Events\SendAuditReport;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\Scan;
use App\Models\Trial;
use App\Models\User;
use App\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $this->setAlerts($scan, $port, $task);
        $this->setScreenshot($port, $task);
        $this->markScanAsCompleted($scan);
    }

    private function setAlerts(Scan $scan, Port $port, array $task): void
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
            ->each(function (array $alert) use ($scan, $port, $asset, $users) {
                try {
                    $type = Str::trim($alert['type']);

                    if (!Str::endsWith($type, '_alert')) {
                        $type .= '_v3_alert';
                    }

                    $vulnerability = Str::limit(trim($alert['vulnerability'] ?? ''), 5000);
                    $remediation = Str::limit(trim($alert['remediation'] ?? ''), 5000);
                    $level = Str::trim($alert['level'] ?? '');
                    $uid = Str::trim($alert['uid'] ?? '');
                    $cve_id = empty($alert['cve_id']) ? null : $alert['cve_id'];
                    $cve_cvss = empty($alert['cve_cvss']) ? null : $alert['cve_cvss'];
                    $cve_vendor = empty($alert['cve_vendor']) ? null : $alert['cve_vendor'];
                    $cve_product = empty($alert['cve_product']) ? null : $alert['cve_product'];
                    $title = Str::trim($alert['title'] ?? '');

                    Log::debug("Saving alert for scan: ({$scan->ports_scan_id}, {$scan->vulns_scan_id}), alert: {$alert['title']}");
                    $start = microtime(true);

                    /** @var Alert $a */
                    $a = Alert::updateOrCreate([
                        'port_id' => $port->id,
                        'uid' => $uid
                    ], [
                        'port_id' => $port->id,
                        'type' => $type,
                        'vulnerability' => $vulnerability,
                        'remediation' => $remediation,
                        'ai_remediation' => null,
                        'false_positive' => false,
                        'level' => $level,
                        'uid' => $uid,
                        'cve_id' => $cve_id,
                        'cve_cvss' => $cve_cvss,
                        'cve_vendor' => $cve_vendor,
                        'cve_product' => $cve_product,
                        'title' => $title,
                        'flarum_slug' => null, // TODO : remove?
                    ]);

                    $stop = microtime(true);
                    Log::debug("Alert saved for scan: ({$scan->ports_scan_id}, {$scan->vulns_scan_id}), alert: {$alert['title']}, time: " . ((int)ceil($stop - $start)));
                    Log::debug("Caching translations for scan: ({$scan->ports_scan_id}, {$scan->vulns_scan_id}), alert: {$alert['title']}");
                    $start = microtime(true);

                    // Cache translations
                    $a->translated('title');
                    $a->translated('vulnerability');
                    $a->translated('remediation');

                    $stop = microtime(true);
                    Log::debug("Translations cached for scan: ({$scan->ports_scan_id}, {$scan->vulns_scan_id}), alert: {$alert['title']}, time: " . ((int)ceil($stop - $start)));

                    if ($a->isHigh() || $a->isMedium()) {

                        Log::debug("Sending notifications for scan: ({$scan->ports_scan_id}, {$scan->vulns_scan_id}), alert: {$alert['title']}");
                        $start = microtime(true);

                        foreach ($users as $u) {
                            if ($asset->asset === $port->ip) {
                                $u->notify(new Notification("{$port->ip}:{$port->port} - {$a->translated('title')} - {$a->translated('vulnerability')}"));
                            } else {
                                $u->notify(new Notification("{$asset->asset} ({$port->ip}:{$port->port}) - {$a->translated('title')} - {$a->translated('vulnerability')}"));
                            }
                        }

                        $stop = microtime(true);
                        Log::debug("Notifications sent for scan: ({$scan->ports_scan_id}, {$scan->vulns_scan_id}), alert: {$alert['title']}, time: " . ((int)ceil($stop - $start)));
                    }

                    GenerateAiRemediation::dispatch($scan, $port, $a);

                } catch (\Exception $exception) {
                    Log::error("An error occurred while processing scan: ({$scan->ports_scan_id}, {$scan->vulns_scan_id}), alert: {$alert['title']}, error: {$exception->getMessage()}");
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
}
