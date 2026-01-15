<?php

namespace App\Listeners;

use App\Events\SendAuditReport;
use App\Helpers\ApiUtilsFacade as ApiUtils2;
use App\Http\Controllers\Iframes\TimelineController;
use App\Mail\SimpleEmail;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\TimelineItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Collection;

class SendAuditReportListener extends AbstractListener
{
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
        $from = 'cyberbuddy@cywise.io'; // config('towerify.freshdesk.from_email');
        $to = $user->email;

        if (!$user->gets_audit_report) {
            return;
        }

        $assets = Asset::all();

        if ($assets->isEmpty()) {
            return;
        }

        $summary = $this->buildSummary($user, $assets);
        $leaks = $this->buildSectionLeaks($user);
        $vulns = $this->buildSectionVulns($assets);
        $subject = $this->buildEmailSubject($user, $assets);
        $body = ['<table cellspacing="0" cellpadding="0" style="margin: auto;"><tbody>'];
        $body[] = '<tr><td style="font-size: 28px; text-align: center;">Bonjour !</td></tr>';
        $body[] = '<tr><td style="font-size: 16px; line-height: 1.6;">';
        $body[] = $summary;
        $body[] = empty($leaks) && empty($vulns) ?
            "<p>Félicitations ! Vous n'avez aucune action à entreprendre.</p>" :
            "<p>Je vous propose d'effectuer les correctifs suivants :</p>";
        $body[] = $vulns;
        $body[] = $leaks;

        if ($isOnboarding) {
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

        SimpleEmail::sendEmail($subject, '', implode("\n", $body), $to, $from);
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
                <td align=\"center\" style=\"background-color: #fbca3e; padding: 10px 20px; border-radius: 5px;\">                    
                    <a href=\"{$link}\" target=\"_blank\" style=\"color: white; text-decoration: none; font-weight: bold;\">
                      je me connecte à Cywise
                    </a>
                </td>
            </tr>
        ";
    }

    private function buildEmailSubject(User $user, Collection $assets): string
    {
        $nbNewAssets = Asset::where('created_at', '>=', Carbon::now()->subDay())->count();

        $nbLeaks = TimelineController::fetchLeaks($user)
            ->flatMap(fn(TimelineItem $item) => json_decode($item->attributes()['credentials']))
            ->unique()
            ->count();

        $nbHigh = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityHigh()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        $nbMedium = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityMedium()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        $nbLow = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityLow()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        $nbIssues = $nbNewAssets + $nbLeaks + $nbHigh + $nbMedium + $nbLow;

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

        return 'Cywise - Une fuite de données ou compromission a été détectée !';
    }

    private function buildSummary(User $user, Collection $assets): string
    {
        $nbNewAssets = Asset::where('created_at', '>=', Carbon::now()->startOfDay()->subDay())
            ->count();

        $nbLeaks = TimelineController::fetchLeaks($user)
            ->flatMap(fn(TimelineItem $item) => json_decode($item->attributes()['credentials']))
            ->unique()
            ->count();

        $nbDns = $assets->filter(fn(Asset $asset) => $asset->is_monitored && $asset->isDns())
            ->pluck('asset')
            ->unique()
            ->count();

        $nbIpAddresses = $assets->filter(fn(Asset $asset) => $asset->is_monitored)
            ->flatMap(fn(Asset $asset) => $asset->ports()->get())
            ->pluck('ip')
            ->unique()
            ->count();

        $nbHigh = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityHigh()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        $nbMedium = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityMedium()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        $nbLow = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityLow()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0)
            ->count();

        $nbAlerts = $nbHigh + $nbMedium + $nbLow;

        $newAssets = match ($nbNewAssets) {
            0 => '',
            1 => "<li>J'ai mis sous surveillance <b>{$nbNewAssets}</b> nouvel actif durant ces dernières 24h.</li>",
            default => "<li>J'ai mis sous surveillance <b>{$nbNewAssets}</b> nouveaux actifs durant ces dernières 24h.</li>",
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
              {$newAssets}
              {$perimeter}
              {$vulns}
              {$leaks}
            </ul>";
    }

    private function buildSectionLeaks(User $user): string
    {
        $leaks = TimelineController::fetchLeaks($user, Carbon::now()->utc()->subDays(7))
            ->flatMap(fn(TimelineItem $item) => json_decode($item->attributes()['credentials']))
            ->sortBy('leak_date', SORT_NATURAL | SORT_FLAG_CASE)
            ->reverse()
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
            <h3>Identifiants fuités ou compromis</h3>
            <p>Cywise surveille également les fuites de données et compromissions !<p>
            <ul>{$leaks}</ul>
            <p>Si aucune action n'a encore été entreprise, <b>demandez aux utilisateurs concernés de modifier leur mot de passe.</b></p>
        ";
    }

    private function buildSectionVulns(Collection $assets): string
    {
        $high = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityHigh()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0);

        $medium = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityMedium()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0);

        $low = $assets->flatMap(fn(Asset $asset) => $asset->alertsWithCriticalityLow()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0);

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

                $result = ApiUtils2::translate($alert->vulnerability ?? '');

                if ($result['error'] !== false) {
                    $vulnerability = $alert->vulnerability;
                } else {
                    $vulnerability = $result['response'];
                }

                $result = ApiUtils2::translate($alert->remediation ?? '');

                if ($result['error'] !== false) {
                    $remediation = $alert->remediation;
                } else {
                    $remediation = $result['response'];
                }

                $link = route('iframes.assets') . "#aid-{$alert->asset()->id}";

                return "
                    <h3>{$alert->title} {$level}</h3>
                    <p><b>Actif concerné.</b> L'actif concerné est {$alert->asset()?->asset} pointant vers le serveur 
                    {$alert->port?->ip}. Le port {$alert->port?->port} de ce serveur est ouvert et expose un service 
                    {$alert->port?->service} ({$alert->port?->product}).</p>
                    <p><b>Description détaillée.</b> {$vulnerability}</p>
                    <p><b>Correctif à appliquer.</b> {$remediation}</p>
                    {$cve}
                ";
            })
            ->join("\n");
    }
}
