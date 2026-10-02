<?php

namespace App\Services;

use App\Enums\DemoStagesEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fake but realistic data for product demos (local and demo environments only, see DemoStage).
 *
 *   assets (*.acme.example, reserved TLD, never resolves) → scans → ports → alerts
 *   leaks  (*@acme.example)
 *   server (demo-web-01, 203.0.113.20) → osquery events (last hours)
 *
 * Monitored assets get a placeholder next_scan_id so the scheduler never scans them.
 * Alert texts are cached as their own French translation: no LLM call at render.
 */
class DemoData
{
    private const string MARK = 'demo-ui-';
    private const string TLD = 'acme.example';
    private const string SERVER_NAME = 'demo-web-01';
    private const string SERVER_IP = '203.0.113.20';
    private const int CACHE_DAYS = 120;

    public function stage(User $user, DemoStagesEnum $stage): void
    {
        DB::transaction(function () use ($user, $stage) {

            $this->clear($user);

            if ($stage === DemoStagesEnum::EMPTY) {
                return;
            }

            $this->seedAssets($user);
            $this->seedLeaks($user);

            if ($stage === DemoStagesEnum::AGENT) {
                $this->seedServer($user);
            }
        });
    }

    // Removes every asset, leak and server of the user (children first: foreign keys)
    private function clear(User $user): void
    {
        $assetIds = DB::table('am_assets')->where('created_by', $user->id)->pluck('id');
        $scanIds = DB::table('am_scans')->whereIn('asset_id', $assetIds)->pluck('id');
        $portIds = DB::table('am_ports')->whereIn('scan_id', $scanIds)->pluck('id');

        DB::table('am_alerts')->whereIn('port_id', $portIds)->delete();
        DB::table('am_screenshots')->whereIn('port_id', $portIds)->delete();
        DB::table('am_ports_tags')->whereIn('port_id', $portIds)->delete();
        DB::table('am_ports')->whereIn('id', $portIds)->delete();
        DB::table('am_assets')->whereIn('id', $assetIds)->update(['cur_scan_id' => null, 'next_scan_id' => null, 'prev_scan_id' => null]);
        DB::table('am_scans')->whereIn('id', $scanIds)->delete();
        DB::table('am_assets_tags')->whereIn('asset_id', $assetIds)->delete();
        DB::table('am_assets')->whereIn('id', $assetIds)->delete();
        DB::table('am_leaks')->where('created_by', $user->id)->delete();

        $serverIds = DB::table('ynh_servers')->where('created_by', $user->id)->pluck('id');

        foreach (['ynh_osquery', 'ynh_osquery_latest_events', 'ynh_osquery_packages', 'ynh_ssh_traces'] as $table) {
            DB::table($table)->whereIn('ynh_server_id', $serverIds)->delete();
        }
        DB::table('ynh_servers')->whereIn('id', $serverIds)->delete();
    }

    private function seedAssets(User $user): void
    {
        $now = now();

        foreach ($this->assets() as $i => $a) {

            $createdAt = $now->copy()->subDays($a['days']);
            $assetId = DB::table('am_assets')->insertGetId([
                'asset' => $a['asset'],
                'type' => $a['type'],
                'tld' => $a['type'] === 'DNS' ? self::TLD : null,
                'is_monitored' => $a['monitored'],
                'auto_monitor_new_subdomains' => $a['auto'],
                'created_by' => $user->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($a['tags'] as $tag) {
                DB::table('am_assets_tags')->insert(['asset_id' => $assetId, 'tag' => $tag, 'created_by' => $user->id, 'created_at' => $now, 'updated_at' => $now]);
            }

            if (!$a['monitored']) {
                continue;
            }

            // Placeholder "scan in progress" (no dates, so Scan::removeDanglingScans keeps it).
            // TriggerScan skips assets with a next_scan_id: no real scan is ever started.
            $scanId = self::MARK . "{$user->id}-{$i}";
            $holdId = self::MARK . "hold-{$user->id}-{$i}";
            DB::table('am_scans')->insert(['asset_id' => $assetId, 'ports_scan_id' => $holdId, 'created_at' => $createdAt, 'updated_at' => $createdAt]);

            foreach ($a['ports'] as $p) {
                $this->seedPort($a['asset'], $a['type'] === 'IP' ? $a['asset'] : '203.0.113.' . (40 + $i), $assetId, $scanId, $p, $createdAt, $i);
            }

            // Link once the scans exist (foreign keys on cur_scan_id / next_scan_id)
            DB::table('am_assets')->where('id', $assetId)->update(['cur_scan_id' => $scanId, 'next_scan_id' => $holdId]);
        }
    }

    // One scan row per port, all sharing the asset's ports_scan_id (am_ports.scan_id is unique)
    private function seedPort(string $hostname, string $ip, int $assetId, string $scanId, array $p, Carbon $createdAt, int $rank): void
    {
        $scan = DB::table('am_scans')->insertGetId([
            'asset_id' => $assetId,
            'ports_scan_id' => $scanId,
            'vulns_scan_id' => $scanId,
            'ports_scan_begins_at' => $createdAt, 'ports_scan_ends_at' => $createdAt,
            'vulns_scan_begins_at' => $createdAt, 'vulns_scan_ends_at' => $createdAt,
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
        $portId = DB::table('am_ports')->insertGetId([
            'scan_id' => $scan,
            'hostname' => $hostname,
            'ip' => $ip,
            'port' => $p['port'],
            'protocol' => 'tcp',
            'service' => $p['service'],
            'product' => $p['product'],
            'ssl' => in_array($p['port'], [443, 993]),
            'closed' => false,
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);

        foreach ($p['tags'] as $tag) {
            DB::table('am_ports_tags')->insert(['port_id' => $portId, 'tag' => $tag, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
        }

        // Spread alerts over the last hours so the timeline looks alive
        foreach ($p['alerts'] as $k => [$level, $title, $cve, $type, $vuln, $fix, $hasScript]) {
            $updatedAt = now()->subHours(($rank * 7) + $k);
            DB::table('am_alerts')->insert([
                'port_id' => $portId,
                'type' => $type,
                'title' => $title,
                'level' => $level,
                'uid' => self::MARK . $type . '-' . $portId,
                'cve_id' => $cve,
                'vulnerability' => $vuln,
                'remediation' => $fix,
                'ai_remediation' => $hasScript ? '#!/bin/bash' . PHP_EOL . '# demo' : null,
                'created_at' => $updatedAt, 'updated_at' => $updatedAt,
            ]);
            $this->cacheFr($title);
            $this->cacheFr($vuln);
            $this->cacheFr($fix);
        }
    }

    // LeaksProcedure lists rows with a leak_date
    private function seedLeaks(User $user): void
    {
        $leaks = [
            [5, 'PRIVATE_ULP_INFOSTEALER', 'iris.dupuis', 'https://app.' . self::TLD . '/login', 'Haj*****a1!'],
            [5, 'PRIVATE_ULP_INFOSTEALER', 'marc.lefevre', 'https://app.' . self::TLD . '/login', 'Sol*****24'],
            [19, 'COMBOLIST', 'contact', null, 'exa*****le'],
            [34, 'PRIVATE_ULP_INFOSTEALER', 'nadia.benali', 'https://mail.' . self::TLD, 'Nb!*****99'],
            [92, 'COMBOLIST', 'support', 'https://www.linkedin.com', 'Sup*****t!'],
            [137, null, 'thomas.roux', null, 'Tro*****x1'],
        ];
        $now = now();

        foreach ($leaks as [$daysAgo, $type, $mailbox, $website, $password]) {
            DB::table('am_leaks')->insert([
                'created_by' => $user->id, 'leak_date' => $now->copy()->subDays($daysAgo)->toDateString(), 'leak_type' => $type,
                'email' => $mailbox . '@' . self::TLD, 'website' => $website, 'password' => $password,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    // Server + IoCs and shell commands over the last hours (EventsProcedure window: 2 days)
    private function seedServer(User $user): void
    {
        $now = now();
        $serverId = DB::table('ynh_servers')->insertGetId([
            'name' => self::SERVER_NAME, 'ip_address' => self::SERVER_IP, 'created_by' => $user->id,
            'is_ready' => true, 'added_with_curl' => true, 'platform' => 'linux', 'secret' => self::MARK . 'secret-' . $user->id,
            'created_at' => $now->copy()->subDays(4), 'updated_at' => $now,
        ]);

        // A few IoCs of decreasing severity
        $pick = fn(int $min, int $max, int $n) => DB::table('ynh_osquery_rules')
            ->where('enabled', true)->whereBetween('score', [$min, $max])->orderByDesc('score')->limit($n)->get();
        $rules = $pick(75, 100, 1)->concat($pick(50, 74, 2))->concat($pick(25, 49, 2));

        foreach ($rules as $k => $rule) {
            $columns = ['cmdline' => 'demo', 'path' => '/usr/bin/demo', 'pid' => 4242 + $k];
            $this->seedEvent($serverId, $rule->id, $rule->name, $columns, $now->copy()->subHours(3 + $k * 5));
        }

        // System events (YnhOsquery::message() knows how to describe shell commands)
        $shellHistory = DB::table('ynh_osquery_rules')->where('name', 'shell_history')->first();

        if (!$shellHistory) {
            return;
        }
        foreach ([['deploy', 'sudo systemctl restart nginx'], ['root', 'apt-get update && apt-get upgrade -y']] as $k => [$username, $command]) {
            $this->seedEvent($serverId, $shellHistory->id, 'shell_history', ['username' => $username, 'command' => $command], $now->copy()->subHours(2 + $k * 9));
        }
    }

    private function seedEvent(int $serverId, int $ruleId, string $name, array $columns, Carbon $at): void
    {
        DB::table('ynh_osquery')->insert([
            'ynh_server_id' => $serverId,
            'ynh_osquery_rule_id' => $ruleId,
            'row' => 0,
            'name' => $name,
            'host_identifier' => self::SERVER_NAME,
            'calendar_time' => $at,
            'unix_time' => $at->timestamp,
            'epoch' => 0,
            'counter' => 0,
            'numerics' => false,
            'columns' => json_encode($columns),
            'action' => 'added',
            'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    // Same key as ChunkAssistant::translate (en → fr)
    private function cacheFr(?string $text): void
    {
        if (empty($text)) {
            return;
        }
        Cache::put('translation:en:fr:' . md5($text), $text, now()->addDays(self::CACHE_DAYS));
    }

    // Alerts: [level, title, cve, type, vulnerability, remediation, has AI script]
    private function assets(): array
    {
        return [
            [
                'asset' => 'www.' . self::TLD, 'type' => 'DNS', 'monitored' => true, 'auto' => true, 'tags' => ['prod'], 'days' => 12,
                'ports' => [
                    ['port' => 443, 'service' => 'https', 'product' => 'nginx 1.18.0', 'tags' => ['nginx', 'web-servers'], 'alerts' => [
                        ['High', 'Laravel <5.5.21 - Divulgation d\'informations', 'CVE-2017-16894', 'laravel-env-disclosure',
                            'Le fichier .env de l\'application Laravel est accessible publiquement à l\'URL https://www.acme.example/.env et expose des secrets (clés d\'API, mots de passe de base de données).',
                            'Bloquer l\'accès au fichier .env dans la configuration du serveur web et mettre à jour Laravel vers une version supérieure ou égale à 5.5.21. Changer ensuite tous les secrets exposés.', true],
                        ['High', 'Fichier .env accessible publiquement', null, 'generic-env-disclosure',
                            'Le fichier https://www.acme.example/.env est accessible sans authentification.',
                            'Bloquer l\'accès au fichier ou le supprimer du répertoire public.', true],
                        ['Medium', 'Fichier \'phpunit.xml\' accessible', null, 'phpunit-xml-exposure',
                            'Le fichier https://www.acme.example/phpunit.xml est accessible publiquement et révèle la structure du projet.',
                            'Retirer les fichiers de développement du répertoire public du site.', false],
                        ['Low', 'En-tête HSTS absent', null, 'missing-hsts',
                            'Le site ne renvoie pas l\'en-tête Strict-Transport-Security : un attaquant peut tenter de forcer une connexion non chiffrée.',
                            'Ajouter l\'en-tête « Strict-Transport-Security: max-age=31536000; includeSubDomains ».', false],
                    ]],
                    ['port' => 80, 'service' => 'http', 'product' => 'nginx 1.18.0', 'tags' => ['nginx'], 'alerts' => []],
                ],
            ],
            [
                'asset' => 'api.' . self::TLD, 'type' => 'DNS', 'monitored' => true, 'auto' => false, 'tags' => ['prod'], 'days' => 9,
                'ports' => [
                    ['port' => 443, 'service' => 'https', 'product' => 'Apache httpd 2.4.49', 'tags' => ['web-servers'], 'alerts' => [
                        ['Critical', 'PHP-CGI - Injection d\'arguments', 'CVE-2024-4577', 'php-cgi-argument-injection',
                            'Le serveur exécute PHP en mode CGI dans une version vulnérable : un attaquant peut exécuter du code à distance.',
                            'Mettre à jour PHP (8.1.29, 8.2.20, 8.3.8 ou plus récent) ou désactiver le mode CGI.', true],
                        ['High', 'Apache HTTP Server 2.4.49 - Traversée de répertoires', 'CVE-2021-41773', 'apache-path-traversal',
                            'La version d\'Apache permet de lire des fichiers hors de la racine du site, voire d\'exécuter du code si mod_cgi est actif.',
                            'Mettre à jour Apache vers la version 2.4.51 ou plus récente.', false],
                        ['Low', 'Certificat SSL auto-signé', null, 'self-signed-ssl',
                            'Le certificat présenté n\'est pas signé par une autorité reconnue : les navigateurs affichent un avertissement.',
                            'Installer un certificat émis par une autorité reconnue (par exemple Let\'s Encrypt).', false],
                    ]],
                ],
            ],
            [
                'asset' => 'staging.' . self::TLD, 'type' => 'DNS', 'monitored' => true, 'auto' => false, 'tags' => ['staging'], 'days' => 5,
                'ports' => [
                    ['port' => 8080, 'service' => 'http', 'product' => 'Node.js Express', 'tags' => [], 'alerts' => [
                        ['Medium', 'Node.js Express en mode développement', null, 'express-dev-mode',
                            'L\'application répond avec des traces d\'erreur détaillées (NODE_ENV=development).',
                            'Définir NODE_ENV=production sur l\'environnement exposé.', false],
                        ['Medium', 'Listing de répertoire activé', null, 'directory-listing',
                            'Le contenu du répertoire /uploads est listé publiquement.',
                            'Désactiver l\'indexation des répertoires dans la configuration du serveur.', false],
                    ]],
                ],
            ],
            [
                'asset' => 'mail.' . self::TLD, 'type' => 'DNS', 'monitored' => true, 'auto' => false, 'tags' => ['prod'], 'days' => 20,
                'ports' => [
                    ['port' => 25, 'service' => 'smtp', 'product' => 'Postfix smtpd', 'tags' => [], 'alerts' => [
                        ['Low', 'La bannière SMTP révèle la version du serveur', null, 'smtp-banner',
                            'La bannière du serveur SMTP indique le logiciel et sa version, ce qui aide un attaquant à cibler ses attaques.',
                            'Masquer la version dans la bannière (smtpd_banner).', false],
                    ]],
                    ['port' => 993, 'service' => 'imaps', 'product' => 'Dovecot imapd', 'tags' => [], 'alerts' => []],
                ],
            ],
            [
                'asset' => '203.0.113.10', 'type' => 'IP', 'monitored' => true, 'auto' => false, 'tags' => [], 'days' => 3,
                'ports' => [
                    ['port' => 22, 'service' => 'ssh', 'product' => 'OpenSSH 7.4', 'tags' => ['ssh'], 'alerts' => [
                        ['Medium', 'OpenSSH obsolète', 'CVE-2023-38408', 'openssh-outdated',
                            'La version d\'OpenSSH est obsolète et affectée par des vulnérabilités connues.',
                            'Mettre à jour OpenSSH vers la dernière version stable.', false],
                    ]],
                ],
            ],
            ['asset' => 'legacy.' . self::TLD, 'type' => 'DNS', 'monitored' => false, 'auto' => false, 'tags' => [], 'days' => 30, 'ports' => []],
        ];
    }
}
