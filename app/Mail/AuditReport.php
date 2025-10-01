<?php

namespace App\Mail;

use App\Models\Alert;
use App\Models\Asset;
use App\Models\User;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryPackage;
use App\Models\YnhServer;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** @deprecated */
class AuditReport extends Mailable
{
    use Queueable, SerializesModels;

    private Collection $alertsHigh;
    private Collection $alertsMedium;
    private Collection $alertsLow;
    private Collection $assetsMonitored;
    private Collection $assetsNotMonitored;
    private Collection $assetsDiscovered;
    private Collection $events;
    private Collection $metrics;
    private Collection $vulnerablePackagesHigh;
    private Collection $vulnerablePackagesMedium;
    private Collection $vulnerablePackagesLow;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Collection $events, Collection $metrics, Collection $alertsHigh, Collection $alertsMedium, Collection $alertsLow, Collection $assetsMonitored, Collection $assetsNotMonitored, Collection $assetsDiscovered, Collection $vulnerablePackages)
    {
        $this->events = $events;
        $this->metrics = $metrics;
        $this->alertsHigh = $alertsHigh;
        $this->alertsMedium = $alertsMedium;
        $this->alertsLow = $alertsLow;
        $this->assetsMonitored = $assetsMonitored;
        $this->assetsNotMonitored = $assetsNotMonitored;
        $this->assetsDiscovered = $assetsDiscovered;
        $this->vulnerablePackagesHigh = $vulnerablePackages
            ->filter(fn(YnhOsqueryPackage $package) => $package->urgency === 'high')
            ->unique(fn($item) => $item->ynh_server_id . $item->package . $item->package_version . $item->fixed_version . $item->cve);
        $this->vulnerablePackagesMedium = $vulnerablePackages
            ->filter(fn(YnhOsqueryPackage $package) => $package->urgency === 'medium')
            ->unique(fn($item) => $item->ynh_server_id . $item->package . $item->package_version . $item->fixed_version . $item->cve);
        $this->vulnerablePackagesLow = $vulnerablePackages
            ->filter(fn(YnhOsqueryPackage $package) => $package->urgency === 'low')
            ->unique(fn($item) => $item->ynh_server_id . $item->package . $item->package_version . $item->fixed_version . $item->cve);
    }

    public static function create(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $servers = YnhServer::forUser($user);
        $cutOffTime = Carbon::now()->subDay();
        $alerts = Asset::where('is_monitored', true)
            ->get()
            ->flatMap(fn(Asset $asset) => $asset->alerts()->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0);
        $alertsHigh = $alerts->filter(fn(Alert $alert) => $alert->isHigh());
        $alertsMedium = $alerts->filter(fn(Alert $alert) => $alert->isMedium());
        $alertsLow = $alerts->filter(fn(Alert $alert) => $alert->isLow());
        $assetsMonitored = Asset::where('is_monitored', true)->orderBy('asset')->get();
        $assetsNotMonitored = Asset::where('is_monitored', false)->orderBy('asset')->get();
        $assetsDiscovered = Asset::where('created_at', '>=', $cutOffTime)->orderBy('asset')->get();
        $events = YnhOsquery::suspiciousEvents($servers, $cutOffTime);
        $metrics = YnhOsquery::suspiciousMetrics($servers, $cutOffTime);
        $vulnerablePackages = YnhOsqueryPackage::vulnerablePackages($servers);

        return [
            'is_empty' => $events->count() <= 0 &&
                $metrics->count() <= 0 &&
                $alerts->count() <= 0 &&
                $assetsMonitored->count() <= 0 &&
                $assetsNotMonitored->count() <= 0 &&
                $assetsDiscovered->count() <= 0 &&
                $vulnerablePackages->count() <= 0,
            'report' => new AuditReport($events, $metrics, $alertsHigh, $alertsMedium, $alertsLow, $assetsMonitored, $assetsNotMonitored, $assetsDiscovered, $vulnerablePackages),
        ];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $events = '';
        if ($this->events->count() > 0) {
            if ($this->events->count() === 1) {
                $events .= "{$this->events->count()} évènement anormal";
            } else {
                $events .= "{$this->events->count()} évènements anormaux";
            }
        }
        if ($this->metrics->count() > 0) {
            if (!empty($events)) {
                $events .= ", ";
            }
            if ($this->metrics->count() === 1) {
                $events .= "{$this->metrics->count()} métrique anormale";
            } else {
                $events .= "{$this->metrics->count()} métriques anormales";
            }
        }
        if ($this->alertsHigh->count() > 0) {
            if (!empty($events)) {
                $events .= ", ";
            }
            if ($this->alertsHigh->count() === 1) {
                $events .= "{$this->alertsHigh->count()} service avec une vulnérabilité critique";
            } else {
                $events .= "{$this->alertsHigh->count()}  services avec une vulnérabilité critique";
            }
        }
        if ($this->vulnerablePackagesHigh->count() > 0) {
            if (!empty($events)) {
                $events .= ", ";
            }
            if ($this->vulnerablePackagesHigh->count() === 1) {
                $events .= "{$this->vulnerablePackagesHigh->count()} package avec une vulnérabilité critique";
            } else {
                $events .= "{$this->vulnerablePackagesHigh->count()}  packages avec une vulnérabilité critique";
            }
        }
        if ($this->assetsDiscovered->count() > 0) {
            if (!empty($events)) {
                $events .= ", ";
            }
            if ($this->assetsDiscovered->count() === 1) {
                $events .= "{$this->assetsDiscovered->count()} nouvel actif découvert";
            } else {
                $events .= "{$this->assetsDiscovered->count()} nouveaux actifs découverts";
            }
        }
        $events = empty($events) ? '' : "({$events})";
        return $this
            ->from(config('towerify.freshdesk.from_email'), 'Support')
            ->subject("Cywise : Rapport d'audit {$events}")
            ->markdown('modules.adversary-meter.email.audit-report', [
                "events" => $this->events,
                "metrics" => $this->metrics,
                "alerts_high" => $this->alertsHigh,
                "alerts_medium" => $this->alertsMedium,
                "alerts_low" => $this->alertsLow,
                "assets_monitored" => $this->assetsMonitored,
                "assets_not_monitored" => $this->assetsNotMonitored,
                "assets_discovered" => $this->assetsDiscovered,
                "vulnerable_packages_high" => $this->vulnerablePackagesHigh,
                "vulnerable_packages_medium" => $this->vulnerablePackagesMedium,
                "vulnerable_packages_low" => $this->vulnerablePackagesLow,
            ]);
    }
}
