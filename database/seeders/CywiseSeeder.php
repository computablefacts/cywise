<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DbConfig\DbAppConfigInterface;
use Illuminate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Wave\Plan;
use Wave\Setting;
use Wave\Theme;

class CywiseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->setupConfig(); // Should be call first
        $this->setupPermissionsRolesAndPlans();
        $this->setupWave();
        $this->setupCywiseAdmin();
        $this->setupOssecRules();
        $this->setupOsqueryRules();
        $this->setupCyberFrameworks();
        $this->updateAccountsData();
    }

    private function setupConfig(): void
    {
        $app_env = config('app.env');
        $appConfigClass = sprintf('Database\Seeders\DbConfig\%sDbAppConfig', Str::ucfirst($app_env));

        if (!class_exists($appConfigClass)) {
            Log::warning('Class ' . $appConfigClass . ' not found for app.env ' . $app_env);
            $appConfigClass = 'Database\Seeders\DbConfig\DefaultDbAppConfig';
            if (!class_exists($appConfigClass)) {
                throw new \Exception('Default class ' . $appConfigClass . ' not found.');
            }
        }

        /** @var DbAppConfigInterface $appConfig */
        $appConfig = new $appConfigClass();
        $this->setupConfigInDb($appConfig->getParams());

        // Read config from DB again
        app_config_override();
    }

    private function setupConfigInDb(array $configs): void
    {
        foreach ($configs as $key => $value) {
            if (Str::startsWith($key, 'encrypted:')) {
                $key = Str::chopStart($key, 'encrypted:');
                AppConfig::updateOrCreate(['key' => $key], [
                    'key' => $key,
                    'value' => cywise_unhash($value),
                    'is_encrypted' => true,
                ]);
            } else {
                AppConfig::updateOrCreate(['key' => $key], [
                    'is_encrypted' => false,
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }
    }

    private function setupWave(): void
    {
        Setting::updateOrCreate([
            'key' => 'site.title',
        ], [
            'display_name' => 'Site Title',
            'value' => 'Cywise',
            'details' => '',
            'type' => 'text',
            'order' => 1,
            'group' => 'Site',
        ]);
        Setting::updateOrCreate([
            'key' => 'site.linkedin',
        ], [
            'display_name' => 'LinkedIn',
            'value' => 'https://www.linkedin.com/company/cywise/',
            'details' => '',
            'type' => 'text',
            'order' => 1,
            'group' => 'Site',
        ]);
        Setting::updateOrCreate([
            'key' => 'site.instagram',
        ], [
            'display_name' => 'Instagram',
            'value' => 'https://www.instagram.com/cywise_cybersec/',
            'details' => '',
            'type' => 'text',
            'order' => 1,
            'group' => 'Site',
        ]);
        Setting::updateOrCreate([
            'key' => 'site.facebook',
        ], [
            'display_name' => 'Facebook',
            'value' => 'https://www.facebook.com/profile.php?viewas=100000686899395&id=61577113076576',
            'details' => '',
            'type' => 'text',
            'order' => 1,
            'group' => 'Site',
        ]);
        Setting::updateOrCreate([
            'key' => 'site.description',
        ], [
            'display_name' => 'Site Description',
            'value' => 'La solution de Cybersécurité pour TPE et PME',
            'details' => '',
            'type' => 'text',
            'order' => 2,
            'group' => 'Site',
        ]);
        if (Setting::query()->where('key', 'site.google_analytics_tracking_id')->whereNull('value')->exists()) {
            Setting::updateOrCreate([
                'key' => 'site.google_analytics_tracking_id',
            ], [
                'display_name' => 'Google Analytics Tracking ID',
                'value' => null,
                'details' => '',
                'type' => 'text',
                'order' => 3,
                'group' => 'Site',
            ]);
        }
        Theme::where('folder', 'anchor')->update(['active' => 0]);
        Theme::updateOrCreate([
            'folder' => 'cywise',
        ], [
            'name' => 'Cywise Theme',
            'folder' => 'cywise',
            'active' => 1,
            'version' => 1.0
        ]);
    }

    private function setupPermissionsRolesAndPlans(): void
    {
        // Create or update roles associated to plans
        Role::updateOrCreate([
            'name' => Role::ADMIN,
            'guard_name' => 'web',
        ], [
            'description' => 'This is the role you will have as the developer and administrator.',
        ]);

        Role::updateOrCreate([
            'name' => Role::REGISTERED,
            'guard_name' => 'web',
        ], [
            'description' => 'This is the default user role. This is the default role for a newly registered user.',
        ]);

        /** @var Role $essential */
        $essential = Role::updateOrCreate([
            'name' => Role::ESSENTIAL_PLAN,
            'guard_name' => 'web',
        ], [
            'description' => 'This is a role associated with a Essential Subscriber Plan.',
        ]);

        /** @var Role $standard */
        $standard = Role::updateOrCreate([
            'name' => Role::STANDARD_PLAN,
            'guard_name' => 'web',
        ], [
            'description' => 'This is a role associated with a Standard Subscriber Plan.',
        ]);

        /** @var Role $premium */
        $premium = Role::updateOrCreate([
            'name' => Role::PREMIUM_PLAN,
            'guard_name' => 'web',
        ], [
            'description' => 'This is a role associated with a Premium Subscriber plan.',
        ]);

        // Create or update plans
        Plan::where('name', 'Essential')
            ->update([
                'active' => false,
                'role_id' => $essential->id,
            ]); // Deal with legacy plan name

        Plan::updateOrCreate([
            'name' => config('towerify.stripe.plans.essential.name'),
        ], [
            'description' => config('towerify.stripe.plans.essential.description'),
            'features' => config('towerify.stripe.plans.essential.features'),
            'role_id' => $essential->id,
            'default' => 0,
            'monthly_price' => config('towerify.stripe.plans.essential.monthly_price'),
            'monthly_price_id' => config('towerify.stripe.plans.essential.monthly_price_id'),
            'yearly_price' => config('towerify.stripe.plans.essential.yearly_price'),
            'yearly_price_id' => config('towerify.stripe.plans.essential.yearly_price_id'),
            'onetime_price' => config('towerify.stripe.plans.essential.onetime_price'),
            'onetime_price_id' => config('towerify.stripe.plans.essential.onetime_price_id'),
            'currency' => '€',
            'sort_order' => 1,
        ]);

        Plan::updateOrCreate([
            'name' => config('towerify.stripe.plans.standard.name'),
        ], [
            'description' => config('towerify.stripe.plans.standard.description'),
            'features' => config('towerify.stripe.plans.standard.features'),
            'role_id' => $standard->id,
            'default' => 1,
            'monthly_price' => config('towerify.stripe.plans.standard.monthly_price'),
            'monthly_price_id' => config('towerify.stripe.plans.standard.monthly_price_id'),
            'yearly_price' => config('towerify.stripe.plans.standard.yearly_price'),
            'yearly_price_id' => config('towerify.stripe.plans.standard.yearly_price_id'),
            'onetime_price' => config('towerify.stripe.plans.standard.onetime_price'),
            'onetime_price_id' => config('towerify.stripe.plans.standard.onetime_price_id'),
            'currency' => '€',
            'sort_order' => 2,
        ]);

        Plan::updateOrCreate([
            'name' => config('towerify.stripe.plans.premium.name'),
        ], [
            'description' => config('towerify.stripe.plans.premium.description'),
            'features' => config('towerify.stripe.plans.premium.features'),
            'role_id' => $premium->id,
            'default' => 0,
            'monthly_price' => config('towerify.stripe.plans.premium.monthly_price'),
            'monthly_price_id' => config('towerify.stripe.plans.premium.monthly_price_id'),
            'yearly_price' => config('towerify.stripe.plans.premium.yearly_price'),
            'yearly_price_id' => config('towerify.stripe.plans.premium.yearly_price_id'),
            'onetime_price' => config('towerify.stripe.plans.premium.onetime_price'),
            'onetime_price_id' => config('towerify.stripe.plans.premium.onetime_price_id'),
            'currency' => '€',
            'sort_order' => 3,
        ]);

        // Remove unused plans
        Plan::whereIn('name', ['Essential', 'basic', 'pro'])->delete();

        // Detach unused roles
        $deprecated = ['administrator', 'limited administrator', 'basic end user', 'basic', 'pro'];

        Role::whereIn('name', $deprecated)->each(fn(Role $role) => $role->permissions()->detach());

        User::query()
            ->whereHas('roles', fn($query) => $query->whereIn('name', $deprecated))
            ->each(function (User $user) use ($deprecated) {

                $user->roles()
                    ->wherePivotIn('role_id', Role::whereIn('name', $deprecated)->pluck('id'))
                    ->detach();

                if (!$user->hasRole(Role::REGISTERED)) {
                    $user->assignRole(Role::REGISTERED);
                }
            });

        // Create missing roles
        foreach (Role::ROLES as $role => $permissions) {

            Log::debug("Creating role {$role}...");

            /** @var Role $role */
            $role = Role::firstOrcreate(['name' => $role]);
            $role->permissions()->detach();
        }
        foreach (Role::ROLES as $role => $permissions) {

            /** @var Role $role */
            $role = Role::where('name', $role)->first();

            // Create missing permissions
            foreach ($permissions as $permission) {

                Log::debug("Creating permission {$permission}...");

                if (Str::startsWith($permission, 'call.')) {
                    $perm = Permission::firstOrCreate(
                        ['name' => $permission],
                        ['guard_name' => 'web'] // TODO : use api instead
                    );
                }
                if (Str::startsWith($permission, 'view.')) {
                    $perm = Permission::firstOrCreate(
                        ['name' => $permission],
                        ['guard_name' => 'web']
                    );
                }
            }

            // Attach permissions to role
            foreach ($permissions as $permission) {

                Log::debug("Attaching permission {$permission} to role {$role->name}...");

                $perm = Permission::where('name', $permission)->firstOrFail();
                $role->permissions()->syncWithoutDetaching($perm);
            }
        }

        // Remove unused permissions
        $permissions = Role::all()
            ->flatMap(fn(Role $role) => $role->permissions()->get())
            ->map(fn(\Spatie\Permission\Models\Permission $perm) => $perm->name)
            ->unique()
            ->values()
            ->toArray();

        Permission::whereNotIn('name', $permissions)->delete();

        // Remove unused roles
        Role::whereIn('name', $deprecated)->delete();
    }

    private function setupCywiseAdmin(): void
    {
        // Create super admin
        $email = config('towerify.admin.email');
        $username = config('towerify.admin.username');
        $password = config('towerify.admin.password');
        /** @var User $user */
        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'verified' => true,
            ]
        );

        // Add the 'admin' role to the user
        $admin = Role::where('name', Role::ADMIN)->firstOrFail();
        $user->roles()->syncWithoutDetaching($admin);
    }

    private function setupOssecRules(): void
    {
        $policies = [
            $this->cisNginx(),
            $this->cisApache(),
            $this->cisIis(),
            $this->cisWeb(),
            $this->cisUnixAudit(),
            $this->cisWin10(),
            $this->cisWin11(),
            $this->cisWin2016(),
            $this->cisWin2019(),
            $this->cisWin2022(),
            $this->cisDebian7(),
            $this->cisDebian8(),
            $this->cisDebian9(),
            $this->cisDebian10(),
            $this->cisDebian11(),
            $this->cisDebian12(),
            $this->cisUbuntu1404(),
            $this->cisUbuntu1604(),
            $this->cisUbuntu1804(),
            $this->cisUbuntu2004(),
            $this->cisUbuntu2204(),
            $this->cisCentOs6(),
            $this->cisCentOs7(),
            $this->cisCentOs8(),
        ];

        Log::debug('Parsing rules...');

        $ok = 0;
        $ko = 0;

        $frameworks = [];

        foreach ($policies as $policy) {

            $requirements = $policy['requirements'];
            $checks = $policy['checks'];
            $policyId = $policy['policy']['id'] ?? '';
            $policyName = $policy['policy']['name'] ?? '';
            $policyDescription = $policy['policy']['description'] ?? '';

            Log::debug("Importing policies {$policyName}...");

            $title = $requirements['title'];
            $condition = $requirements['condition'] ?? 'all';
            $references = isset($policy['policy']['references']) ? collect($policy['policy']['references'])->join(",") : '';
            $expressions = collect($requirements['rules'])->join(";\n");
            $str = "
                [{$title}] [$condition] [{$references}]
                {$expressions};
            ";
            $rules = \App\Helpers\OssecRulesParser::parse($str);

            $pol = \App\Models\YnhOssecPolicy::updateOrCreate([
                'uid' => $policyId,
            ], [
                'uid' => $policyId,
                'name' => $policyName,
                'description' => $policyDescription,
                'references' => $policy['policy']['references'] ?? [],
                'requirements' => $rules,
            ]);

            foreach ($checks as $check) {
                try {
                    $id = $check['id'];
                    $title = $check['title'] ?? '';
                    $description = $check['description'] ?? '';
                    $rationale = $check['rationale'] ?? '';
                    $impact = $check['impact'] ?? '';
                    $remediation = $check['remediation'] ?? '';
                    $compliance = $check['compliance'] ?? [];
                    $condition = $check['condition'] ?? 'all';
                    $references = isset($check['references']) ? collect($check['references'])->join(',') : '';
                    $expressions = collect($check['rules'])->join(";\n");
                    $str = "[{$title}] [$condition] [{$references}]\n{$expressions};";
                    $rules = \App\Helpers\OssecRulesParser::parse($str);
                    if (count($rules) <= 0 || count($rules['rules']) <= 0) {
                        Log::warning($str);
                        Log::warning($rules);
                        $ko++;
                    } else {
                        \App\Models\YnhOssecCheck::updateOrCreate([
                            'uid' => $id,
                        ], [
                            'ynh_ossec_policy_id' => $pol->id,
                            'uid' => $id,
                            'title' => $title,
                            'description' => $description,
                            'rationale' => $rationale,
                            'impact' => $impact,
                            'remediation' => $remediation,
                            'compliance' => $compliance,
                            'references' => array_filter(explode(',', $references), fn(string $ref) => !empty($ref)),
                            'requirements' => $rules,
                            'rule' => $str,
                        ]);
                        $frameworks = array_merge($frameworks, collect($compliance)->flatMap(fn(array $compliance) => array_keys($compliance))->toArray());
                        $ok++;
                    }
                } catch (\Exception $e) {
                    Log::warning($e->getMessage());
                    $ko++;
                }
            }
        }

        $frameworks = collect($frameworks)
            ->map(fn(string $framework) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::replace('_', ' ', $framework)))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        Log::debug('Frameworks:');
        Log::debug($frameworks);

        $total = $ok + $ko;

        Log::debug("{$total} rules parsed. {$ok} OK. {$ko} KO.");
    }

    private function setupOsqueryRules(): void
    {
        $mitreAttckMatrix = $this->mitreAttckMatrix();

        foreach ($mitreAttckMatrix as $rule) {
            \App\Models\YnhMitreAttck::updateOrCreate([
                'uid' => \Illuminate\Support\Str::replace('.', '/', $rule['id'])
            ], [
                'uid' => \Illuminate\Support\Str::replace('.', '/', $rule['id']),
                'title' => $rule['title'],
                'tactics' => $rule['tactics'],
                'description' => $rule['description'],
            ]);
        }

        $rules = $this->osquery();
        \App\Models\YnhOsqueryRule::query()
            ->whereNull('created_by')
            ->update(['enabled' => false]);

        foreach ($rules as $rule) {
            $rule['created_by'] = null; // this rule is available to all users
            \App\Models\YnhOsqueryRule::updateOrCreate(['name' => $rule['name']], $rule);
        }
    }

    private function updateAccountsData(): void
    {
        \App\Models\Tenant::query()->chunkById(100, function ($tenants) {
            /** @var \App\Models\Tenant $tenant */
            foreach ($tenants as $tenant) {
                User::query()
                    ->where('tenant_id', $tenant->id)
                    ->chunkById(100, function ($users) {
                        /** @var User $user */
                        foreach ($users as $user) {
                            $user->actAs();
                            $user->init();
                        }
                    });
            }
        });
    }

    private function setupCyberFrameworks(): void
    {
        $this->importFramework('seeders/frameworks/anssi');
        $this->importFramework('seeders/frameworks/dora');
        $this->importFramework('seeders/frameworks/gdpr');
        $this->importFramework('seeders/frameworks/ncsc');
        $this->importFramework('seeders/frameworks/nist');
        $this->importFramework('seeders/frameworks/owasp');
        $this->importFramework('seeders/frameworks/nis');
        $this->importFramework('seeders/frameworks/nis2');
    }

    private function importFramework(string $root): void
    {
        $path = database_path($root);
        foreach (glob($path . '/*.json') as $file) {
            Log::debug("Importing {$file}...");
            $json = json_decode(Illuminate\Support\Facades\File::get($file), true);
            if (Str::endsWith($file, 'anssi-guide-hygiene.json') ||
                Str::endsWith($file, 'anssi-genai-security-recommendations-1.0.json')) {
                \App\Models\YnhFramework::where('name', $json['name'])->delete();
                Log::debug("{$file} skipped.");
            } else {
                \App\Models\YnhFramework::updateOrCreate([
                    'name' => $json['name'],
                ], [
                    'name' => $json['name'],
                    'description' => $json['description'],
                    'copyright' => \Illuminate\Support\Str::limit($json['copyright'], 187, '[...]'),
                    'version' => $json['version'],
                    'provider' => $json['provider'],
                    'locale' => $json['locale'],
                    'file' => $root . '/' . basename($file, '.json') . '.jsonl.gz',
                ]);
                Log::debug("{$file} imported.");
            }
        }
    }

    private function mitreAttckMatrix(): array
    {
        // https://github.com/bgenev/impulse-xdr/blob/main/managerd/main/helpers/data/mitre_matrix.json
        $path = database_path('seeders/misc/mitre_matrix.json');
        $json = Illuminate\Support\Facades\File::get($path);
        return json_decode($json, true);
    }

    private function osquery(): array
    {
        // Sources :
        // - https://github.com/osquery/osquery/blob/master/packs/hardware-monitoring.conf
        // - https://github.com/osquery/osquery/blob/master/packs/incident-response.conf
        // - https://github.com/osquery/osquery/blob/master/packs/it-compliance.conf
        // - https://github.com/osquery/osquery/blob/master/packs/osquery-monitoring.conf
        // - https://github.com/osquery/osquery/blob/master/packs/ossec-rootkit.conf
        // - https://github.com/osquery/osquery/blob/master/packs/vuln-management.conf
        $path = database_path('seeders/misc/osquery.json');
        $json = Illuminate\Support\Facades\File::get($path);
        return json_decode($json, true);
    }

    private function cisWin10(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/windows/cis_win10_enterprise.yml');
        return Yaml::parse($yaml);
    }

    private function cisWin11(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/windows/cis_win11_enterprise.yml');
        return Yaml::parse($yaml);
    }

    private function cisWin2016(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/windows/cis_win2016.yml');
        return Yaml::parse($yaml);
    }

    private function cisWin2019(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/windows/cis_win2019.yml');
        return Yaml::parse($yaml);
    }

    private function cisWin2022(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/windows/cis_win2022.yml');
        return Yaml::parse($yaml);
    }

    private function cisDebian7(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/debian/cis_debian7.yml');
        return Yaml::parse($yaml);
    }

    private function cisDebian8(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/debian/cis_debian8.yml');
        return Yaml::parse($yaml);
    }

    private function cisDebian9(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/debian/cis_debian9.yml');
        return Yaml::parse($yaml);
    }

    private function cisDebian10(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/debian/cis_debian10.yml');
        return Yaml::parse($yaml);
    }

    private function cisDebian11(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/debian/cis_debian11.yml');
        return Yaml::parse($yaml);
    }

    private function cisDebian12(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/debian/cis_debian12.yml');
        return Yaml::parse($yaml);
    }

    private function cisUbuntu1404(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/ubuntu/cis_ubuntu14_04.yml');
        return Yaml::parse($yaml);
    }

    private function cisUbuntu1604(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/ubuntu/cis_ubuntu16_04.yml');
        return Yaml::parse($yaml);
    }

    private function cisUbuntu1804(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/ubuntu/cis_ubuntu18_04.yml');
        return Yaml::parse($yaml);
    }

    private function cisUbuntu2004(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/ubuntu/cis_ubuntu20_04.yml');
        return Yaml::parse($yaml);
    }

    private function cisUbuntu2204(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/ubuntu/cis_ubuntu22_04.yml');
        return Yaml::parse($yaml);
    }

    private function cisCentOs6(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/centos/6/cis_centos6_linux.yml');
        return Yaml::parse($yaml);
    }

    private function cisCentOs7(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/centos/7/cis_centos7_linux.yml');
        return Yaml::parse($yaml);
    }

    private function cisCentOs8(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/centos/8/cis_centos8_linux.yml');
        return Yaml::parse($yaml);
    }

    private function cisUnixAudit(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/generic/sca_unix_audit.yml');
        return Yaml::parse($yaml);
    }

    private function cisNginx(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/nginx/cis_nginx_1.yml');
        return Yaml::parse($yaml);
    }

    private function cisApache(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/applications/cis_apache_24.yml');
        return Yaml::parse($yaml);
    }

    private function cisIis(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/applications/cis_iis_10.yml');
        return Yaml::parse($yaml);
    }

    private function cisWeb(): array
    {
        $yaml = file_get_contents('https://raw.githubusercontent.com/wazuh/wazuh-agent/refs/heads/main/etc/ruleset/sca/applications/web_vulnerabilities.yml');
        return Yaml::parse($yaml);
    }
}
