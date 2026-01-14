<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\PromptsProvider;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int port_id
 * @property ?string uid
 * @property string type
 * @property ?string level
 * @property ?string title
 * @property ?string vulnerability
 * @property ?string remediation
 * @property ?string cve_id
 * @property ?string cve_cvss
 * @property ?string cve_vendor
 * @property ?string cve_product
 */
class Alert extends Model
{
    use HasFactory;

    protected $table = 'am_alerts';

    protected $fillable = [
        'port_id',
        'type',
        'vulnerability',
        'remediation',
        'ai_remediation',
        'level',
        'uid',
        'cve_id',
        'cve_cvss',
        'cve_vendor',
        'cve_product',
        'title',
        'flarum_slug',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function asset(): Asset
    {
        return Asset::select('am_assets.*')
            ->join('am_scans', 'am_scans.asset_id', '=', 'am_assets.id')
            ->join('am_ports', 'am_ports.scan_id', '=', 'am_scans.id')
            ->join('am_alerts', 'am_alerts.port_id', '=', 'am_ports.id')
            ->where('am_alerts.id', $this->id)
            ->first();
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function events(?int $attackerId = null): Builder
    {
        /** @var array $ips */
        $ips = config('towerify.adversarymeter.ip_addresses');
        $cveId = trim(Str::upper($this->cve_id));
        $events = HoneypotEvent::query()
            ->join('am_honeypots', 'am_honeypots.id', '=', 'am_honeypots_events.honeypot_id')
            ->where('am_honeypots_events.event', 'cve_tested')
            ->whereLike('am_honeypots_events.details', 'CVE-%')
            ->whereNotIn('am_honeypots_events.ip', $ips)
            ->whereRaw("TRIM(UPPER(am_honeypots_events.details)) = '{$cveId}'");
        if ($attackerId) {
            $events->where('am_honeypots_events.attacker_id', $attackerId);
        }
        return $events;
    }

    public function isCritical(): bool
    {
        return $this->level === 'Critical';
    }

    public function isHigh(): bool
    {
        return $this->isCritical() || $this->level === 'High';
    }

    public function isMedium(): bool
    {
        return $this->level === 'Medium';
    }

    public function isLow(): bool
    {
        return $this->level === 'Low';
    }
    
    public function isUnverified(): bool
    {
        return $this->level === 'High (unverified)';
    }

    public function generateRemediation(string $mode = 'both'): array
    {
        $category = $this->detectVulnerabilityCategory();
        $context = $this->gatherSecurityContext($category);
        $results = [];

        if($mode === 'explanation' || $mode === 'both') {
            $results['explanation'] = $this->processLlmPart($category,$context, 'explanation');
        }

        if ($mode === 'script' || $mode === 'both') {
            $results['script'] = $this->processLlmPart($category, $context, 'script');
        }
        return $results;
    }

    private function detectVulnerabilityCategory(): string
    {
        $type = strtolower($this->type);

        if(Str::contains($type, ['quickhits_file', 'config_file', 'backup_file', 'file_alert', 'file_v3'])) {
            return 'file_exposed';
        }
        if (Str::contains($type, ['weak_cipher', 'ssl_certificate', 'tls_', 'cipher'])) {
            return "weak_cipher";
        }
        if (!empty($this->cve_id)) {
            return "cve";
        }
        return "general";
    }

    private function gatherSecurityContext(string $category): array
    {
        $port = $this->port;
        $context = [
            'ip' => $port->ip ?? 'N/A',
            'port' => $port->port ?? 0,
            'protocol' => $port->protocol ?? 'tcp',
            'vulnerability' => $this->vulnerability,
            'title' => $this->title,
            'technology' => 'unknown',
            'cve_id' => $this->cve_id,
        ];

        if ($category === 'file_exposed') {
            if (preg_match('/url\s*:(?:http?:\/\/)?([^\s<>"\']+)/i', $this->vulnerability, $matches)) {
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

        if ($context['technology'] === 'unknown' && in_array($category, ['file_exposed', 'weak_cipher'])) {
            $context['technology'] = $this->probeTechnology($context['ip'], $context['port']);
        }

        if ($category === 'cve' && $this->cve_id) {
            $context['cve_info'] = "NIST NVD: https://nvd.nist.gov/vuln/detail/" . strtoupper($this->cve_id);
        }

        return $context;
    }

    private function probeTechnology(string $ip, int $port): string
    {
        try {
            foreach (["https://$ip:$port", "http://$ip:$port"] as $url) {
                $response = Http::withOptions(['verify' => false])->timeout(3)->head($url);
                $server = strtolower($response->header('Server', ''));
                if (Str::contains($server, 'nginx')) return 'nginx';
                if (Str::contains($server, 'apache')) return 'apache';
            }
        } catch (\Exception $e) {}
        return 'unknown';
    }

    private function processLlmPart(string $category, array $context, string $type): string
    {
        if ($category === 'file_exposed' && !empty($context['file_content']) && $type === 'explanation') {
            $fpPrompt = PromptsProvider::provide('false_positive_prompt', [
                'CONTENT' => $context['file_content'],
                'TITLE' => $this->title,
                'TYPE' => $this->type
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
            'title' => $this->title,
            'type' => $this->type,
            'vulnerability' => $this->vulnerability,
            'remediation' => $this->remediation,
            'technology' => $context['technology'],
            'technology_upper' => strtoupper($context['technology']),
            'cve_id' => $this->cve_id ?? 'N/A',
            'domain' => $context['ip'], 
            'filename' => basename(parse_url($context['exposed_url'] ?? '', PHP_URL_PATH) ?: 'file'),
            'analysis_context' => '',
            'risky_parts' => 'Analyse en cours...',
            'cve_info' => $context['cve_info'] ?? '',
        ];

        if ($type === 'script') {
            $scriptDir = base_path("database/seeders/remediations");
            $scriptFile = match($category) {
                'file_exposed' => "script_{$context['technology']}.bash",
                'weak_cipher' => "fix_weak_ciphers_{$context['technology']}.bash",
                default => null
            };
            
            if ($scriptFile && file_exists("$scriptDir/$scriptFile")) {
                $vars['script_content'] = file_get_contents("$scriptDir/$scriptFile");
            } else {
                return "Erreur : Template de script bash introuvable ({$scriptFile}).";
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
                'script' => 'explanation_only_prompt',
            ],
            'general' => [
                'explanation' => 'explanation_only_prompt',
                'script' => 'explanation_only_prompt',
            ]
        ];

        return $map[$category][$type] ?? 'explanation_only_prompt';
    }
}
