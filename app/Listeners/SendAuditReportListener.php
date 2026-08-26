<?php

namespace App\Listeners;

use App\Events\SendAuditReport;
use App\Http\Procedures\EventsProcedure;
use App\Http\Procedures\LeaksProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\User;
use App\Models\YnhServer;
use App\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Parsedown;

class SendAuditReportListener extends AbstractListener
{
    public $timeout = 30 * 60; // 30 mn

    public function viaQueue(): string
    {
        return self::CRITICAL;
    }

    protected function handle2($event)
    {
        if (!($event instanceof SendAuditReport)) {
            throw new \Exception('Invalid event type!');
        }

        $isOnboarding = $event->isOnboarding;
        $user = $event->user;
        $user->actAs(); // otherwise the tenant will not be properly set
        $from = config('towerify.freshdesk.from_email');

        if (!$user->gets_audit_report) {
            return;
        }

        $assets = Asset::all();

        if ($assets->isEmpty()) {
            return;
        }

        Log::debug("Building audit report to {$user->email}...");
        Log::debug("Loading IoCs for {$user->email}...");

        $iocs = $this->buildSectionIoCs($user);

        Log::debug("Loading summary for {$user->email}...");

        $summary = $this->buildSummary($user, $assets);

        Log::debug("Loading leaks for {$user->email}...");

        $leaks = $this->buildSectionLeaks($user);

        Log::debug("Loading vulnerabilities for {$user->email}...");

        $vulns = $this->buildSectionVulns($assets);

        Log::debug("Assembling audit report for {$user->email}...");

        $subject = $this->buildEmailSubject($user, $assets, $isOnboarding);
        $body = ['<table cellspacing="0" cellpadding="0" style="margin: auto;"><tbody>'];
        $body[] = '<tr><td style="font-size: 28px; text-align: center;">Bonjour !</td></tr>';
        $body[] = '<tr><td style="font-size: 16px; line-height: 1.6;">';
        $body[] = $summary;
        $body[] = empty($leaks) && empty($vulns) ?
            "<p>Félicitations ! Vous n'avez aucune action à entreprendre.</p>" :
            "<p>Cet email met en avant les 10 vulnérabilités les plus critiques identifiées lors de notre dernière analyse. Pour consulter la liste complète des vulnérabilités détectées, je vous invite à vous connecter directement à la plateforme.</p><p>Afin de renforcer rapidement la sécurité de votre infrastructure, je vous recommande de prioriser les correctifs suivants :</p>";
        $body[] = $vulns;
        $body[] = $leaks;
        $body[] = $iocs;

        if ($isOnboarding) {

            Log::debug("Adding onboarding CTA for {$user->email}...");

            $body[] = '<p>Pour découvrir comment corriger vos vulnérabilités et renforcer la sécurité de votre infrastructure, finalisez votre inscription à Cywise :</p>';
            $body[] = '</td></tr>';
            $body[] = $this->buildEmailCta($user);
            $body[] = '<tr><td style="font-size: 16px; line-height: 1.6;">';
        }

        $body[] = '<p>Je reste à votre disposition pour toute question ou assistance supplémentaire. Merci encore pour votre confiance en Cywise !</p>';
        $body[] = '<p>Bien à vous,</p>';
        $body[] = '<p>CyberBuddy</p>';
        $body[] = '</td></tr>';
        $body[] = '</tbody></table>';

        Log::debug("Sending audit report to {$user->email}...");

        $user->notify(Notification::viaEmail(implode("\n", $body), $subject, $from));
    }

    private function buildEmailCta(User $user): string
    {
        $link = route('password.reset', [
            'token' => app(PasswordBroker::class)->createToken($user),
            'email' => $user->email,
            'reason' => 'Finalisez votre inscription en créant un mot de passe',
            'action' => 'Créer mon mot de passe',
        ]);

        return "
            <tr>
              <td style=\"padding-top: 20px;\">
                <p><b>Dernière étape pour créer votre compte utilisateur :</b> cliquez ci-dessous pour choisir votre mot de passe et activer votre compte. Ce lien expirera dans 60 minutes.</p>
              </td>
            </tr>
            <tr>
                <td align=\"center\" style=\"background-color: #fbca3e; padding: 10px 20px; border-radius: 5px;\">                    
                    <a href=\"{$link}\" target=\"_blank\" style=\"color: white; text-decoration: none; font-weight: bold;\">
                      je crée mon mot de passe
                    </a>
                </td>
            </tr>
            <tr>
              <td style=\"padding-bottom: 20px;\">
                <p>Si le bouton ne fonctionne pas, <a href=\"https://www.cywise.io/auth/password/reset\" target=\"_blank\"><b>cliquez ici</b></a>.</p>
              </td>
            </tr>
        ";
    }

    private function buildEmailSubject(User $user, Collection $assets, bool $isOnboarding): string
    {
        Log::debug("Building subject for user {$user->email}...");

        $nbNewAssets = Asset::where('created_at', '>=', Carbon::now()->subDay())->count();

        Log::debug("{$nbNewAssets} new assets found for user {$user->email}");

        if ($isOnboarding) {
            $nbLeaks = $this->fetchLeaks($user)->unique()->count();
            Log::debug("{$nbLeaks} leaks found for user {$user->email}");
        } else {
            $minDate = Carbon::now()->utc()->subDays(7);
            $nbLeaks = $this->fetchLeaks($user, $minDate)->unique()->count();
            Log::debug("{$nbLeaks} new leaks found for user {$user->email} since {$minDate->format('Y-m-d')}");
        }

        $nbHigh = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityHigh()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        Log::debug("{$nbHigh} vulnerabilities high found for user {$user->email}");

        $nbMedium = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityMedium()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        Log::debug("{$nbMedium} vulnerabilities medium found for user {$user->email}");

        $nbLow = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityLow()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        Log::debug("{$nbLow} vulnerabilities low found for user {$user->email}");

        $nbIssues = $nbNewAssets + $nbLeaks + $nbHigh + $nbMedium + $nbLow;

        Log::debug("{$nbIssues} alerts found for user {$user->email}");

        if ($nbIssues === 0) {
            return 'Cywise - Tout va bien !';
        }
        if ($nbHigh > 0) {
            return "Cywise - {$nbHigh} vulnérabilités doivent être corrigées !";
        }
        if ($nbMedium > 0) {
            return "Cywise - {$nbMedium} vulnérabilités devraient être corrigées !";
        }
        if ($nbLow > 0) {
            return "Cywise - {$nbLow} vulnérabilités ne posent pas un risque de sécurité immédiat.";
        }
        if ($nbNewAssets > 0) {
            return "Cywise - {$nbNewAssets} nouveaux actifs ont été ajoutés !";
        }
        if ($nbLeaks > 0) {
            return $isOnboarding ?
                "Cywise - {$nbLeaks} fuites de données ou compromissions ont été découvertes !" :
                "Cywise - {$nbLeaks} nouvelles fuites de données ou compromissions ont été découvertes !";
        }
        return 'Cywise - Tout va bien !';
    }

    private function buildSummary(User $user, Collection $assets): string
    {
        Log::debug("Building summary for user {$user->email}...");

        $nbNewAssetsFound = Asset::where('created_at', '>=', Carbon::now()->startOfDay()->subWeek())
            ->count();

        Log::debug("{$nbNewAssetsFound} new assets found for user {$user->email}");

        $nbNewAssetsMonitored = Asset::where('created_at', '>=', Carbon::now()->startOfDay()->subWeek())
            ->where('is_monitored', true)
            ->count();

        Log::debug("{$nbNewAssetsMonitored} new assets monitored for user {$user->email}");

        $nbLeaks = $this->fetchLeaks($user)->unique()->count();

        Log::debug("{$nbLeaks} leaks found for user {$user->email}");

        $nbDns = $assets->filter(fn(Asset $asset) => $asset->is_monitored && $asset->isDns())
            ->pluck('asset')
            ->unique()
            ->count();

        Log::debug("{$nbDns} DNS found for user {$user->email}");

        $nbIpAddresses = $assets->filter(fn(Asset $asset) => $asset->is_monitored)
            ->flatMap(fn(Asset $asset) => $asset->ports()->get())
            ->pluck('ip')
            ->unique()
            ->count();

        Log::debug("{$nbIpAddresses} IP addresses found for user {$user->email}");

        $nbHigh = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityHigh()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        Log::debug("{$nbHigh} vulnerabilities high found for user {$user->email}");

        $nbMedium = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityMedium()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        Log::debug("{$nbMedium} vulnerabilities medium found for user {$user->email}");

        $nbLow = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityLow()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        Log::debug("{$nbLow} vulnerabilities low found for user {$user->email}");

        $nbAlerts = $nbHigh + $nbMedium + $nbLow;

        Log::debug("{$nbAlerts} alerts found for user {$user->email}");

        $noIpAssets = $assets->filter(fn(Asset $asset) => $asset->isIpAddressMissing())
            ->map(fn(Asset $asset) => $asset->asset)
            ->unique()
            ->sort();
        $cloudflareAssets = $assets->filter(fn(Asset $asset) => $asset->isProtectedByCloudflare())
            ->map(fn(Asset $asset) => $asset->asset)
            ->unique()
            ->sort();
        $noIpSection = '';
        $cloudflareSection = '';

        if ($noIpAssets->isNotEmpty()) {
            $noIpSection = "<li>J'ai découvert <b>{$noIpAssets->count()}</b> domaines sans adresse IP (ils pourraient être supprimés) :<ul>";
            $noIpSection .= $noIpAssets->map(fn(string $asset) => "<li>{$asset}</li>")->join('');
            $noIpSection .= '</ul></li>';
        }
        if ($cloudflareAssets->isNotEmpty()) {
            $url = app_url();
            $cloudflareSection = "<li>J'ai découvert <b>{$cloudflareAssets->count()}</b> actifs protégés par Cloudflare (n'oubliez pas d'autoriser <a href=\"{$url}/ips-v4.txt\">nos adresses IP</a> !) :<ul>";
            $cloudflareSection .= $cloudflareAssets->map(fn(string $asset) => "<li>{$asset}</li>")->join('');
            $cloudflareSection .= '</ul></li>';
        }

        $newAssetsFound = match ($nbNewAssetsFound) {
            0 => '',
            1 => "<li>J'ai découvert <b>{$nbNewAssetsFound}</b> nouvel actif.</li>",
            default => "<li>J'ai découvert <b>{$nbNewAssetsFound}</b> nouveaux actifs.</li>",
        };

        $newAssetsMonitored = match ($nbNewAssetsMonitored) {
            0 => '',
            1 => "<li>J'ai mis sous surveillance <b>{$nbNewAssetsMonitored}</b> nouvel actif.</li>",
            default => "<li>J'ai mis sous surveillance <b>{$nbNewAssetsMonitored}</b> nouveaux actifs.</li>",
        };

        $leaks = match ($nbLeaks) {
            0 => '',
            1 => "<li>J'ai trouvé <b>{$nbLeaks}</b> identifiant fuité ou compromis.</li>",
            default => "<li>J'ai trouvé <b>{$nbLeaks}</b> identifiants fuités ou compromis.</li>",
        };

        $perimeter = match ($nbDns + $nbIpAddresses) {
            0 => '',
            default => "<li>J'ai analysé <b>{$nbDns}</b> domaine" . ($nbDns > 1 ? 's' : '') . " et <b>{$nbIpAddresses}</b> serveur" . ($nbIpAddresses > 1 ? 's' : '') . ".</li>",
        };

        $high = match ($nbHigh) {
            0 => '',
            1 => "<li><b>{$nbHigh}</b> vulnérabilité critique <b>doit</b> être corrigée.</li>",
            default => "<li><b>{$nbHigh}</b> vulnérabilités critiques <b>doivent</b> être corrigées.</li>",
        };

        $medium = match ($nbMedium) {
            0 => '',
            1 => "<li><b>{$nbMedium}</b> vulnérabilité de criticité moyenne <b>devrait</b> être corrigée.</li>",
            default => "<li><b>{$nbMedium}</b> vulnérabilités de criticité moyenne <b>devraient</b> être corrigées.</li>",
        };

        $low = match ($nbLow) {
            0 => '',
            1 => "<li><b>{$nbLow}</b> vulnérabilité de criticité basse ne pose pas un risque de sécurité immédiat.</li>",
            default => "<li><b>{$nbLow}</b> vulnérabilités de criticité basse ne posent pas un risque de sécurité immédiat.</li>",
        };

        $vulns = $nbAlerts === 0 ?
            '' :
            "<li>J'ai découvert <b>{$nbAlerts}</b> vulnérabilités :<ul>
                {$high}
                {$medium}
                {$low}
            </ul></li>";

        return "
            <p>Voici un résumé des résultats de l'audit :</p>
            <ul>
              {$newAssetsFound}
              {$newAssetsMonitored}
              {$perimeter}
              {$vulns}
              {$noIpSection}
              {$cloudflareSection}
              {$leaks}
            </ul>";
    }

    private function buildSectionLeaks(User $user, int $maxLeaks = 10): string
    {
        $leaks = $this->fetchLeaks($user, Carbon::now()->utc()->subDays(7))
            ->reverse()
            ->take($maxLeaks)
            ->map(function (object $leak) {

                $date = empty($leak->leak_date) ? '' : " (date est. {$leak->leak_date})";
                $password = empty($leak->password) ? '' : " ({$leak->password})";

                return empty($leak->website) ?
                    "<li>L'identifiant <b>{$leak->email}</b>{$password} a été fuité{$date}.</li>" :
                    "<li>L'identifiant <b>{$leak->email}</b>{$password} donnant accès au site web <b>{$leak->website}</b> a été compromis{$date}.</li>";
            })
            ->unique()
            ->join("\n");

        return empty($leaks) ? '' : "
            <h3>Vos {$maxLeaks} derniers dentifiants fuités ou compromis</h3>
            <p>Cywise surveille également les fuites de données et compromissions !<p>
            <ul>{$leaks}</ul>
            <p>Si aucune action n'a encore été entreprise, <b>demandez aux utilisateurs concernés de modifier leur mot de passe.</b></p>
        ";
    }

    private function buildSectionVulns(Collection $assets): string
    {
        $high = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityHigh()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0);

        if ($high->count() < 10) {
            $medium = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityMedium()->get())
                ->filter(fn(Alert $alert) => $alert->is_hidden === 0);
        } else {
            $medium = collect();
        }
        if ($high->count() + $medium->count() < 10) {
            $low = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityLow()->get())
                ->filter(fn(Alert $alert) => $alert->is_hidden === 0);
        } else {
            $low = collect();
        }
        return $high
            ->concat($medium)
            ->concat($low)
            ->map(function (Alert $alert) {

                if ($alert->isHigh()) {
                    $level = '(criticité haute)';
                } elseif ($alert->isMedium()) {
                    $level = '(criticité moyenne)';
                } elseif ($alert->isLow()) {
                    $level = '(criticité basse)';
                } else {
                    $level = '';
                }
                if (empty($alert->cve_id)) {
                    $cve = '';
                } else {
                    $cve = "<p><b>Note.</b> Cette vulnérabilité a pour identifiant <a href=\"https://nvd.nist.gov/vuln/detail/{$alert->cve_id}\">{$alert->cve_id}</a>.</p>";
                }

                $title = $alert->translated('title');
                $vulnerability = $alert->translated('vulnerability');
                $remediation = $alert->translated('remediation');
                $link = route('assets') . "#aid-{$alert->asset()->id}";

                return "
                    <h3>{$title} {$level}</h3>
                    <p><b>Actif concerné.</b> L'actif concerné est {$alert->asset()?->asset} pointant vers le serveur 
                    {$alert->port?->ip}. Le port {$alert->port?->port} de ce serveur est ouvert et expose un service 
                    {$alert->port?->service} ({$alert->port?->product}).</p>
                    <p><b>Description détaillée.</b> {$vulnerability}</p>
                    <p><b>Correctif à appliquer.</b> {$remediation}</p>
                    {$cve}
                ";
            })
            ->take(10)
            ->join("\n");
    }

    private function buildSectionIoCs(User $user): string
    {
        $activity = YnhServer::select('ynh_servers.*')
            ->whereRaw("ynh_servers.is_ready = true")
            ->orderBy('ynh_servers.name')
            ->get()
            ->map(function (YnhServer $server) use ($user) {

                $request = new JsonRpcRequest([
                    'server_id' => $server->id,
                    'include_events' => false,
                ]);
                $request->setUserResolver(fn() => $user);
                $result = (new EventsProcedure())->socOperator($request);

                if ($result['activity'] === 'UNKNOWN') {
                    return '';
                    // return "<li>L'opérateur SOC a rencontré une erreur lors de l'analyse du serveur <b>{$server->name}</b> d'adresse IP {$server->ip()}.</li>";
                }
                if ($result['activity'] === 'NORMAL') {
                    return '';
                    // return "<li>Il n'y a eu aucun événement notable sur le serveur <b>{$server->name}</b> d'adresse IP {$server->ip()} ces derniers jours.</li>";
                }
                return (new Parsedown)->text("**{$server->name} ({$server->ip()})**\n\n{$result['report']}");
            })
            ->filter(fn(string $event) => !empty($event))
            ->sort()
            ->values();

        Log::debug("SOC operator report: " . json_encode(['activity' => $activity]));

        return $activity->isEmpty() ? '' : "<h3>Analyse de l'activité des serveurs</h3>{$activity->implode('')}";
    }

    private function fetchLeaks(User $user, ?Carbon $createdAtOrAfter = null): Collection
    {
        if ($createdAtOrAfter) {
            $request = new JsonRpcRequest(['created_at_or_after' => $createdAtOrAfter->toIso8601String()]);
        } else {
            $request = new JsonRpcRequest();
        }
        $request->setUserResolver(fn() => $user);
        return (new LeaksProcedure())->list($request)['leaks'];
    }
}
