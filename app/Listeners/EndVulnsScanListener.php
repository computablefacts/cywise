<?php

namespace App\Listeners;

use App\Events\EndVulnsScan;
use App\Helpers\ApiUtilsFacade as ApiUtils2;
use App\Helpers\JosianneClient;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Mail\MailCoachSimpleEmail;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\Scan;
use App\Models\TimelineItem;
use App\Models\YnhTrial;
use Carbon\Carbon;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EndVulnsScanListener extends AbstractListener
{
    public static function sendEmailReport(YnhTrial $trial): void
    {
        if ($trial->completed) {
            Log::warning("Trial {$trial->id} is already completed");
            return;
        }

        $user = $trial->createdBy();
        $assets = $trial->assets()->get();
        $scansInProgress = $assets->contains(fn(Asset $asset) => $asset->scanInProgress()->isNotEmpty());

        if ($scansInProgress) {
            Log::warning("Assets are still being scanned for trial {$trial->id}");
            return;
        }

        $query = "SELECT DISTINCT lower(concat(login, '@', login_email_domain)) AS email, concat(url_scheme, '://', url_subdomain, '.', url_domain) AS website, password FROM dumps_login_email_domain WHERE login_email_domain = '{$assets->first()->tld}' ORDER BY email, website ASC";

        Log::debug($query);

        $output = JosianneClient::executeQuery($query);
        $leaks = collect(explode("\n", $output))
            ->filter(fn(string $line) => !empty($line) && $line !== 'ok')
            ->map(function (string $line) {
                return [
                    'email' => Str::trim(Str::before($line, "\t")),
                    'website' => Str::trim(Str::between($line, "\t", "\t")),
                    'password' => self::maskPassword(Str::trim(Str::afterLast($line, "\t"))),
                ];
            })
            ->map(function (array $credentials) {
                // if (preg_match("/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|(([^\s()<>]+|(([^\s()<>]+)))*))+(?:(([^\s()<>]+|(([^\s()<>]+)))*)|[^\s`!()[]{};:'\".,<>?«»“”‘’]))/", $credentials['website'])) {
                if (filter_var($credentials['website'], FILTER_VALIDATE_URL)) {
                    return $credentials;
                }
                return [
                    'email' => $credentials['email'],
                    'website' => '',
                    'password' => $credentials['password'],
                ];
            })
            ->unique(fn(array $credentials) => $credentials['email'] . $credentials['website'] . $credentials['password'])
            ->values();
        $msgLeaks = $leaks->isNotEmpty() ? "<li>J'ai trouvé <b>{$leaks->count()}</b> identifiants fuités ou compromis appartenant au domaine {$assets->first()->tld}.</li>" : "";

        unset($output);

        $onboarding = route('tools.cybercheck', ['hash' => $trial->hash, 'step' => 5]);
        $alerts = $assets->flatMap(fn(Asset $asset) => $asset->alerts()->get())->filter(fn(Alert $alert) => $alert->is_hidden === 0);
        $alertsHigh = $alerts->filter(fn(Alert $alert) => $alert->isHigh());
        $alertsMedium = $alerts->filter(fn(Alert $alert) => $alert->isMedium());
        $alertsLow = $alerts->filter(fn(Alert $alert) => $alert->isLow());
        $nbServers = $alerts->map(fn(Alert $alert) => $alert->port()->ip)->unique()->count();
        $from = config('towerify.freshdesk.from_email');
        $to = $user->email;
        $msgHigh = $alertsHigh->isNotEmpty() ? "<li>J'ai trouvé <b>{$alertsHigh->count()}</b> vulnérabilités critiques qui <b>doivent</b> être corrigées.</li>" : "";
        $msgMedium = $alertsMedium->isNotEmpty() ? "<li>J'ai trouvé <b>{$alertsMedium->count()}</b> vulnérabilités de criticité moyenne qui <b>devraient</b> être corrigées.</li>" : "";
        $msgLow = $alertsLow->isNotEmpty() ? "<li>J'ai trouvé <b>{$alertsLow->count()}</b> vulnérabilités de criticité basse qui ne posent pas un risque de sécurité immédiat.</li>" : "";
        $answer = $alertsHigh->concat($alertsMedium)
            ->concat($alertsLow)
            ->map(function (Alert $alert) {

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
                    $cve = "<p><b>Note.</b> Cette vulnérabilité a pour identifiant <a href=\"https://nvd.nist.gov/vuln/detail/{$alert->cve_id}\">{$alert->cve_id}</a>.</p>";
                }

                $result = ApiUtils2::translate($alert->vulnerability);

                if ($result['error'] !== false) {
                    $vulnerability = $alert->vulnerability;
                } else {
                    $vulnerability = $result['response'];
                }
                return "
                    <h3>{$alert->title} {$level}</h3>
                    <p><b>Actif concerné.</b> L'actif concerné est {$alert->asset()?->asset} pointant vers le serveur 
                    {$alert->port()?->ip}. Le port {$alert->port()?->port} de ce serveur est ouvert et expose un service 
                    {$alert->port()?->service} ({$alert->port()?->product}).</p>
                    <p><b>Description détaillée</b>. {$vulnerability}</p>
                    {$cve}
                ";
            })->join("");

        if ($leaks->isNotEmpty()) {
            $website = $leaks->map(fn(array $leak) => "<li>L'identifiant <b>{$leak['email']}</b> donnant accès à <b>{$leak['website']}</b> a été fuité ou compromis.</li>")
                ->unique()
                ->join("\n");
            $answer .= "
            <h3>Identifiants fuités ou compromis</h3>
            <p>Cywise surveille également les fuites de données et compromissions !<p>
            <ul>
              {$website}
            </ul>
            <p>Si aucune action n'a encore été entreprise, demande aux utilisateurs concernés de modifier leur mot de passe.</p>
            ";
        }

        $subject = "Cywise - Résultats de ton audit de sécurité";

        $beforeCta = "
            <p>Je tiens tout d'abord à te remercier d'avoir testé Cywise. Ta participation est essentielle pour m'aider à améliorer la sécurité de tes systèmes et à protéger tes données sensibles.</p>
            <p>L'idée forte derrière Cywise est de t'aider à améliorer ta sécurité en quelques minutes par semaine.</p>
            <p>Voici un résumé des résultats du test :</p>
            <ul>
              <li>J'ai analysé <b>{$assets->count()}</b> domaines.</li>
              <li>J'ai évalué <b>{$nbServers}</b> serveurs et découvert <b>{$alerts->count()}</b> vulnérabilités.</li>
              {$msgHigh}
              {$msgMedium}
              {$msgLow}
              {$msgLeaks}
            </ul>
            <p>Je te propose d'effectuer les correctifs suivants :</p>
            {$answer}
            <p>Pour retourner à la liste de tes domaines, cliques <a href='{$onboarding}' target='_blank'>ici</a>.</p>
            <p>Pour découvrir comment corriger tes vulnérabilités et renforcer la sécurité de ton infrastructure, finalise ton inscription à Cywise :</p>
        ";

        $ctaLink = route('password.reset', [
            'token' => app(PasswordBroker::class)->createToken($user),
            'email' => $user->email,
            'reason' => 'Finalisez votre inscription en créant un mot de passe',
            'action' => 'Créer mon mot de passe',
        ]);

        $ctaName = "je me connecte à Cywise";

        $afterCta = "
            <p>Enfin, je reste à ta disposition pour toute question ou assistance supplémentaire. Merci encore pour ta confiance en Cywise !</p>
            <p>Bien à toi,</p>
            <p>CyberBuddy</p>
        ";

        self::sendEmail($from, $to, $subject, "Bienvenue !", $beforeCta, $ctaLink, $ctaName, $afterCta);

        // $controller = new AssetController();
        // $assets->each(fn(Asset $asset) => $controller->assetMonitoringEnds($asset));

        $trial->completed = true;
        $trial->save();
    }

    public static function sendEmail(string $from, string $to, string $subject, string $title, string $beforeCta, string $ctaLink = "", string $ctaName = "", string $afterCta = ""): void
    {
        $header = empty($title) ? "" : "
            <tr>
              <td style=\"font-size: 28px; text-align: center;\">
                {$title}
              </td>
            </tr>        
        ";
        $body1 = empty($beforeCta) ? "" : "
            <tr>
              <td style=\"font-size: 16px; line-height: 1.6;\">
                {$beforeCta}
              </td>
            </tr>
        ";
        $cta = empty($ctaLink) || empty($ctaName) ? "" : "
            <tr>
                <td align=\"center\" style=\"background-color: #fbca3e; padding: 10px 20px; border-radius: 5px;\">                    
                    <a href=\"{$ctaLink}\" target=\"_blank\" style=\"color: white; text-decoration: none; font-weight: bold;\">
                      {$ctaName}
                    </a>
                </td>
            </tr>
        ";
        $body2 = empty($afterCta) ? "" : "
            <tr>
              <td style=\"font-size: 16px; line-height: 1.6;\">
                {$afterCta}
              </td>    
            </tr> 
        ";
        MailCoachSimpleEmail::sendEmail($subject, $title, "<table cellspacing=\"0\" cellpadding=\"0\" style=\"margin: auto;\">{$header}{$body1}{$cta}{$body2}</table>", $to, $from);
    }

    private static function maskPassword(string $password, int $size = 3): string
    {
        if (Str::length($password) <= 2) {
            return Str::repeat('*', Str::length($password));
        }
        if (Str::length($password) <= 2 * $size) {
            return self::maskPassword($password, 1);
        }
        return Str::substr($password, 0, $size) . Str::repeat('*', Str::length($password) - 2 * $size) . Str::substr($password, -$size, $size);
    }

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

            // $this->createTimelineItem($scan);
            /** @var Asset $asset */
            $asset = $scan->asset()->firstOrFail();
            /** @var YnhTrial $trial */
            $trial = $asset->trial()->first();

            if ($trial) {
                self::sendEmailReport($trial);
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

                    Alert::updateOrCreate([
                        'port_id' => $port->id,
                        'uid' => trim($alert['uid'])
                    ], [
                        'port_id' => $port->id,
                        'type' => $type,
                        'vulnerability' => Str::limit(trim($alert['vulnerability']), 5000),
                        'remediation' => Str::limit(trim($alert['remediation']), 5000),
                        'level' => trim($alert['level']),
                        'uid' => trim($alert['uid']),
                        'cve_id' => empty($alert['cve_id']) ? null : $alert['cve_id'],
                        'cve_cvss' => empty($alert['cve_cvss']) ? null : $alert['cve_cvss'],
                        'cve_vendor' => empty($alert['cve_vendor']) ? null : $alert['cve_vendor'],
                        'cve_product' => empty($alert['cve_product']) ? null : $alert['cve_product'],
                        'title' => trim($alert['title']),
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

    // Whatever happens during the ports scan, a vuln scan is triggered!
    private function createTimelineItem(Scan $scan): void
    {
        /** @var Asset $asset */
        $asset = $scan->asset()->first();
        if ($asset->cur_scan_id !== $scan->ports_scan_id || $asset->next_scan_id === $scan->ports_scan_id) {
            Log::warning("Asset is still being scanned for scan {$scan->id}");
        } else {
            TimelineItem::fetchAlerts($asset->created_by, null, null, 0, [
                [['asset_id', '=', $asset->id]],
            ])->each(function (TimelineItem $item) {
                $item->deleteItem();
                $item->save();
            });
            $asset->alerts()->get()->each(fn(Alert $alert) => TimelineItem::createAlert($alert->asset()->createdBy(), $scan, $alert));
        }
    }
}
