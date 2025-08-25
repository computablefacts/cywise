<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
    public function run()
    {
        $this->setupConfig(); // Should be call first
        $this->setupTenants();
        $this->setupPermissions();
        $this->setupRoles();
        $this->setupWave();
        $this->setupUsers();
        $this->setupOssecRules();
        $this->setupOsqueryRules();
        $this->fillMissingOsqueryUids();
        $this->setupFrameworks();
        $this->setupUserPromptsAndFrameworks();
    }

    private function setupConfig()
    {
        $app_env = config('app.env');
        if ($app_env == 'local') {
            $this->setupConfigInDb([
                'towerify.website' => 'http://127.0.0.1:8000',

                'towerify.freshdesk.widget_id' => '',
                'towerify.freshdesk.to_email' => 'support@computablefacts.freshdesk.com',
                'towerify.freshdesk.from_email' => 'support@cywise.io',

                'towerify.reports.url' => 'https://dev-reports.cywise.io',
                'towerify.reports.api' => 'https://dev-reports.cywise.io/api/v1',
                'towerify.reports.api_username' => '',
                'towerify.reports.api_password' => '',

                'array:towerify.adversarymeter.ip_addresses' => '62.210.90.152',
                'towerify.adversarymeter.api' => 'https://dev.sentinel-api-rq.apps.sentinel-api.towerify.io/api/v1',
                'towerify.adversarymeter.api_username' => 'sentinel-admin',
                // 'encrypted:towerify.adversarymeter.api_password' => '',
                'towerify.adversarymeter.drop_scan_events_after_x_minutes' => '1440',
                'towerify.adversarymeter.drop_discovery_events_after_x_minutes' => '60',
                'towerify.adversarymeter.days_between_scans' => '5',

                'towerify.cyberbuddy.api' => 'https://dev.generic-rag.dev02.towerify.io',
                'towerify.cyberbuddy.api_username' => 'gr',
                // 'encrypted:towerify.cyberbuddy.api_password' => '',

                'towerify.deepseek.api' => 'https://api.deepseek.com/v1',
                // 'encrypted:towerify.deepseek.api_key' => '',

                'towerify.deepinfra.api' => 'https://api.deepinfra.com/v1/openai',
                // 'encrypted:towerify.deepinfra.api_key' => '',

                'towerify.gemini.api' => 'https://generativelanguage.googleapis.com/v1beta/openai',
                // 'encrypted:towerify.gemini.api_key' => '',

                'array:towerify.telescope.whitelist.usernames' => 'computablefacts.com,hdwsec.fr,mncc.fr',
                'array:towerify.telescope.whitelist.domains' => 'csavelief,engineering,pbrisacier,pduteil,pduteil+dev',

                'array:towerify.performa.whitelist.usernames' => 'computablefacts.com,hdwsec.fr,mncc.fr',
                'array:towerify.performa.whitelist.domains' => 'csavelief,engineering,pbrisacier,pduteil,pduteil+dev',

                'towerify.openai.api' => 'https://api.openai.com/v1',
                // 'encrypted:towerify.openai.api_key' => '',

                // 'encrypted:towerify.scrapfly.api_key' => '',

                // 'encrypted:towerify.scraperapi.api_key' => '',

                'towerify.stripe.key' => '',
                'towerify.stripe.secret' => '',

                'towerify.stripe.plans.essential.name' => 'Essentiel',
                'towerify.stripe.plans.essential.description' => null,
                'towerify.stripe.plans.essential.features' => '',
                'towerify.stripe.plans.essential.monthly_price' => null,
                'towerify.stripe.plans.essential.monthly_price_id' => null,
                'towerify.stripe.plans.essential.yearly_price' => null,
                'towerify.stripe.plans.essential.yearly_price_id' => null,
                'towerify.stripe.plans.essential.onetime_price' => null,
                'towerify.stripe.plans.essential.onetime_price_id' => null,

                'towerify.stripe.plans.standard.name' => 'Standard',
                'towerify.stripe.plans.standard.description' => null,
                'towerify.stripe.plans.standard.features' => '',
                'towerify.stripe.plans.standard.monthly_price' => null,
                'towerify.stripe.plans.standard.monthly_price_id' => null,
                'towerify.stripe.plans.standard.yearly_price' => null,
                'towerify.stripe.plans.standard.yearly_price_id' => null,
                'towerify.stripe.plans.standard.onetime_price' => null,
                'towerify.stripe.plans.standard.onetime_price_id' => null,

                'towerify.stripe.plans.premium.name' => 'Premium',
                'towerify.stripe.plans.premium.description' => null,
                'towerify.stripe.plans.premium.features' => '',
                'towerify.stripe.plans.premium.monthly_price' => null,
                'towerify.stripe.plans.premium.monthly_price_id' => null,
                'towerify.stripe.plans.premium.yearly_price' => null,
                'towerify.stripe.plans.premium.yearly_price_id' => null,
                'towerify.stripe.plans.premium.onetime_price' => null,
                'towerify.stripe.plans.premium.onetime_price_id' => null,

                'towerify.clickhouse.host' => '',
                'towerify.clickhouse.username' => '',
                'towerify.clickhouse.password' => '',
                'towerify.clickhouse.database' => '',

                'towerify.sendgrid.api' => 'https://api.sendgrid.com/v3/mail/send',
                // 'encrypted:towerify.sendgrid.api_key' => '',

                'towerify.josianne.host' => 'clickhouse.apps.josiane.computablefacts.io',
                'towerify.josianne.username' => 'cywise',
                // 'encrypted:towerify.josianne.password' => '',
                'towerify.josianne.database' => '',
            ]);
        } elseif ($app_env == 'dev') {
            $this->setupConfigInDb([
                'towerify.website' => 'https://ngdev.cywise-ui.apps.cywise.io',

                'towerify.freshdesk.widget_id' => '',
                'towerify.freshdesk.to_email' => 'support@computablefacts.freshdesk.com',
                'towerify.freshdesk.from_email' => 'support@cywise.io',

                'towerify.reports.url' => 'https://dev-reports.cywise.io',
                'towerify.reports.api' => 'https://dev-reports.cywise.io/api/v1',
                'towerify.reports.api_username' => '',
                'towerify.reports.api_password' => '',

                'array:towerify.adversarymeter.ip_addresses' => '62.210.90.152',
                'towerify.adversarymeter.api' => 'https://dev.sentinel-api-rq.apps.sentinel-api.towerify.io/api/v1',
                'towerify.adversarymeter.api_username' => 'sentinel-admin',
                'encrypted:towerify.adversarymeter.api_password' => 'LgWyyrgnDQ8ezYpA_FUim35NiUc2tWmQiR/5wGNnc+bS4iJkDSu1jGdqvFCo=',
                'towerify.adversarymeter.drop_scan_events_after_x_minutes' => '1440',
                'towerify.adversarymeter.drop_discovery_events_after_x_minutes' => '60',
                'towerify.adversarymeter.days_between_scans' => '5',

                'towerify.cyberbuddy.api' => 'https://dev.generic-rag.dev02.towerify.io',
                'towerify.cyberbuddy.api_username' => 'gr',
                'encrypted:towerify.cyberbuddy.api_password' => 'fkJ0MDfsZMVpzON#_RFmJerW4CAPYCTKlpwjvRg==',

                'towerify.deepseek.api' => 'https://api.deepseek.com/v1',
                'encrypted:towerify.deepseek.api_key' => 'RSS1ZtHV!uSg4Gvr_T3wUdZm/sso1lWLb4CpCPlVa5b6EXjRE0CWwvl2LkNWJmm6Mx9eXuOb8i53Z04Yd',

                'towerify.deepinfra.api' => 'https://api.deepinfra.com/v1/openai',
                'encrypted:towerify.deepinfra.api_key' => 'ooLU?YcpQj6rQXmB_/qXKpsXygqXJDpGWtkCPJxANYwPAQbIxTN49ZE0Ipqr/CpeDUSXuBZh9PtyqH8Ck',

                'towerify.gemini.api' => 'https://generativelanguage.googleapis.com/v1beta/openai',
                'encrypted:towerify.gemini.api_key' => 'a#juOq4apsMBq&99_iNHi4IdBsswBlkIhwNBYbYNx23AvT6rLsY3kETOang8cka4qANAeASVv9VQYJPEn',

                'array:towerify.telescope.whitelist.usernames' => 'computablefacts.com,hdwsec.fr,mncc.fr',
                'array:towerify.telescope.whitelist.domains' => 'csavelief,engineering,pbrisacier,pduteil,pduteil+dev',

                'array:towerify.performa.whitelist.usernames' => 'computablefacts.com,hdwsec.fr,mncc.fr',
                'array:towerify.performa.whitelist.domains' => 'csavelief,engineering,pbrisacier,pduteil,pduteil+dev',

                'towerify.openai.api' => 'https://api.openai.com/v1',
                'encrypted:towerify.openai.api_key' => '6dbLxUYdsOQiFRcp_xgdMdae7nL5B8uULLU+G3QpXRga0i0qQwPIurAV7T3t/b7WtVn/9etjY6/tFavJfwt9dl5NW2ZHvSIVgKXukMNqQ6k/ZcH1qh9flUu9s2BCW7/YOQJ3hYNAxEanCVglXqazkirUbFwiO5wcsSvg99N0nCoRYVAJNj3/loI8ADfREkYRKEIENyoGNvv7hpPQSvRaGqhE7FR7K7PPzkt84y3FbcR/YJk4IlCRJ7TC1mzA=',

                'encrypted:towerify.scrapfly.api_key' => '2xXcLYRIKdjFlDL&_fhu7isCCrP20g0iuchgOj/CJIIdGKVq/fpk7lSkcDJiQxLcPkZHfjz5/pf5S3AqI',

                'encrypted:towerify.scraperapi.api_key' => 'f3xkpO94jZGaAJE3_yMyYN+Ub8Zhp63k2i7qsbuHKQ2FRbQdh6/agBlfI7hodzDLH9kbWkcMRJFobctTZ',

                'towerify.stripe.key' => '',
                'towerify.stripe.secret' => '',

                'towerify.stripe.plans.essential.name' => 'Essentiel',
                'towerify.stripe.plans.essential.description' => null,
                'towerify.stripe.plans.essential.features' => 'Scan de vulnérabilités, Honeypots pré-configurés, Adresses emails internes compromises, Charte informatique, Cyberbuddy, 15 jours gratuits, Assistance par tickets (réponse sour 48h)',
                'towerify.stripe.plans.essential.monthly_price' => '150',
                'towerify.stripe.plans.essential.monthly_price_id' => '',
                'towerify.stripe.plans.essential.yearly_price' => null,
                'towerify.stripe.plans.essential.yearly_price_id' => null,
                'towerify.stripe.plans.essential.onetime_price' => null,
                'towerify.stripe.plans.essential.onetime_price_id' => null,

                'towerify.stripe.plans.standard.name' => 'Standard',
                'towerify.stripe.plans.standard.description' => null,
                'towerify.stripe.plans.standard.features' => 'Tout ce qui est dans Essentiel, Agent, Honeypots sur des domaines spécifiques, Adresses emails de l\'écosystème compromises, Règles de Hardening par référentiel Cyber, PSSI, 15 jours gratuits, Assistance par tickets (réponse sour 24h)',
                'towerify.stripe.plans.standard.monthly_price' => '400',
                'towerify.stripe.plans.standard.monthly_price_id' => '',
                'towerify.stripe.plans.standard.yearly_price' => null,
                'towerify.stripe.plans.standard.yearly_price_id' => null,
                'towerify.stripe.plans.standard.onetime_price' => null,
                'towerify.stripe.plans.standard.onetime_price_id' => null,

                'towerify.stripe.plans.premium.name' => 'Premium',
                'towerify.stripe.plans.premium.description' => null,
                'towerify.stripe.plans.premium.features' => 'Tout ce qui est dans Premium, CyberBuddy via Teams, SSO, Référentiels additionnels, 15 jours gratuits, Assistance par tickets (réponse sour 6h)',
                'towerify.stripe.plans.premium.monthly_price' => '600',
                'towerify.stripe.plans.premium.monthly_price_id' => '',
                'towerify.stripe.plans.premium.yearly_price' => null,
                'towerify.stripe.plans.premium.yearly_price_id' => null,
                'towerify.stripe.plans.premium.onetime_price' => null,
                'towerify.stripe.plans.premium.onetime_price_id' => null,

                'towerify.clickhouse.host' => '',
                'towerify.clickhouse.username' => '',
                'towerify.clickhouse.password' => '',
                'towerify.clickhouse.database' => '',

                'towerify.sendgrid.api' => 'https://api.sendgrid.com/v3/mail/send',
                'encrypted:towerify.sendgrid.api_key' => 'vkqBP73Ezm3oFqcQ_h5scMEOSW3HC1DcsRJmHI+V2nWMr5TQ4KYJbYePliV8JsObaNKqVqGXl81XGbj445U3oP2O2lyUbClsabgTL/wy0XiWX6KL11uvW+tsN0QE=',

                'towerify.josianne.host' => 'clickhouse.apps.josiane.computablefacts.io',
                'towerify.josianne.username' => 'cywise',
                'encrypted:towerify.josianne.password' => 'BZMMOKdbxEtn?mpc_xZS2m+XhLGIkAQC7rhtpVKG3diisqZkgxGmSrCPkx7o=',
                'towerify.josianne.database' => '',
            ]);
        } elseif ($app_env == 'prod') {
            $this->setupConfigInDb([
                'towerify.website' => 'https://www.cywise.io',

                'towerify.freshdesk.widget_id' => '44000004635',
                'towerify.freshdesk.to_email' => 'support@computablefacts.freshdesk.com',
                'towerify.freshdesk.from_email' => 'support@cywise.io',

                'towerify.reports.url' => 'https://reports.cywise.io',
                'towerify.reports.api' => 'https://reports.cywise.io/api/v1',
                'towerify.reports.api_username' => 'admin',
                'encrypted:towerify.reports.api_password' => 'LTF3#G9jB4skruym_pgXeZrDViPcfevUX+PxiRzn9VgHaypMRLK5nV7H1e98=',

                'array:towerify.adversarymeter.ip_addresses' => '62.210.90.152',
                'towerify.adversarymeter.api' => 'https://prod.sentinel-api.apps.sentinel-api.towerify.io/api/v1',
                'towerify.adversarymeter.api_username' => 'sentinel-admin',
                'encrypted:towerify.adversarymeter.api_password' => '?3GH?I97ayIfEsKN_09a2oY8xo+PkGMmLnezl+xbv5rC4Dlw6mbQCVazhCKY=',
                'towerify.adversarymeter.drop_scan_events_after_x_minutes' => '1440',
                'towerify.adversarymeter.drop_discovery_events_after_x_minutes' => '60',
                'towerify.adversarymeter.days_between_scans' => '5',

                'towerify.cyberbuddy.api' => 'https://prod.generic-rag.dev02.towerify.io',
                'towerify.cyberbuddy.api_username' => 'gr',
                'encrypted:towerify.cyberbuddy.api_password' => 'esfM1S4U3SYsnsSm_sAgO943QigLqfU9K8UGUfJAk0gTUp7ovS8uIbD3U+zM=',

                'towerify.deepseek.api' => 'https://api.deepseek.com/v1',
                'encrypted:towerify.deepseek.api_key' => 'It&C5lRsI!Dq1IsX_ea+wX+Mz22wW95wY1cjq56mDp76lxfeLNrz40RSfe5n9eqflr8lz/TyDsmcqnygh',

                'towerify.deepinfra.api' => 'https://api.deepinfra.com/v1/openai',
                'encrypted:towerify.deepinfra.api_key' => '2sb?kbsVy4yE3gFw_xNKSEzcwSsF2E8gHmym8okt84QGYCy9T9Sp0e7u3PvkTO/C9z1qEebGyHaJ8Sj3V',

                'towerify.gemini.api' => 'https://generativelanguage.googleapis.com/v1beta/openai',
                'encrypted:towerify.gemini.api_key' => 'bYg2zlwYpWlULSJ9_boRGCkKbpzTr5NHeZ9ZBqaCkeaCmK2/lpr8mFLCuPcUNvKPlesCldGWo4L7Mb9rc',

                'array:towerify.telescope.whitelist.usernames' => 'computablefacts.com,hdwsec.fr,mncc.fr',
                'array:towerify.telescope.whitelist.domains' => 'csavelief,engineering,pbrisacier,pduteil',

                'array:towerify.performa.whitelist.usernames' => 'computablefacts.com,hdwsec.fr,mncc.fr',
                'array:towerify.performa.whitelist.domains' => 'csavelief,engineering,pbrisacier,pduteil',

                'towerify.openai.api' => 'https://api.openai.com/v1',
                'encrypted:towerify.openai.api_key' => 'Tg2yWb33U2tIItmd_IU0txV/5KxNv9+bwBnqkMVYujizOT2ErSz59maW2/r71cKbi5KxikqLZ5D6U+AdreucHfXdxd4LN7ZgX8LGI/V2J1qgagPa/xEValIH34MoGeJEIUN11zaKIyBpp2YYV++iQAkGgI9wdjcYGWtr6EjZZELv9tw8OiSrW1GzEf8O7Z86bHiE7DYqxX//3caIzba7ASNMbdAEclXAnMjZa5JstmPEiyU4U6+B5zCTc4ok=',

                'encrypted:towerify.scrapfly.api_key' => '!jr0dNpdZSWuc5cg_BVlBZwoXazqe4LuXvFWaO6+Vnv/wYQx8751JOkXHyLT+4pPsJhbFHC55pwpdhgMI',

                'encrypted:towerify.scraperapi.api_key' => 'MQK32#KsaMczwIRb_0sY+5xNWzDIUWU9ZS0lGlsToaed8w1Yf2OaTmysiUbVS8vgGLgWcwVkfgL1LIHPx',

                'encrypted:towerify.stripe.key' => 'emqKPsH94Gp4!ziy_fBp0naOHlnV28A5fDfY8hWrQy6w9QGUja3fZeIXG4n6214U4OIP+eFJQRwRO5qJ3sFJvM9wMBuwUhpXnMLkT2gpRUG1kdzHVIaGSQeq/6bhwJTF3xNwDTDcvQf70F73uldge+VVroUKdqFgKZiClNQ==',
                'encrypted:towerify.stripe.secret' => 'r0ZRSP5WHOFJ?H8z_031Fm8fYryEEHjmkax3GINurDzaYtrE0bpWVIAI0y8zxlwqDxoPRKEJ5y0W6Dq+wJp3JEA5FoL+NuZ42bN5Jd8blDdwJqeSqyKsTPWZKmr3sdpNUinLRxf4XxFRRXMpYOCRDOzqrk0qZeOw4HLRfkg==',

                'towerify.stripe.plans.essential.name' => 'Essentiel',
                'towerify.stripe.plans.essential.description' => null,
                'towerify.stripe.plans.essential.features' => 'Scan de vulnérabilités, Honeypots pré-configurés, Adresses emails internes compromises, Charte informatique, Cyberbuddy, 15 jours gratuits, Assistance par tickets (réponse sour 48h)',
                'towerify.stripe.plans.essential.monthly_price' => '150',
                'towerify.stripe.plans.essential.monthly_price_id' => 'price_1Qee99DHRqrzgOLGOfaVSPbt',
                'towerify.stripe.plans.essential.yearly_price' => null,
                'towerify.stripe.plans.essential.yearly_price_id' => null,
                'towerify.stripe.plans.essential.onetime_price' => null,
                'towerify.stripe.plans.essential.onetime_price_id' => null,

                'towerify.stripe.plans.standard.name' => 'Standard',
                'towerify.stripe.plans.standard.description' => null,
                'towerify.stripe.plans.standard.features' => 'Tout ce qui est dans Essentiel, Agent, Honeypots sur des domaines spécifiques, Adresses emails de l\'écosystème compromises, Règles de Hardening par référentiel Cyber, PSSI, 15 jours gratuits, Assistance par tickets (réponse sour 24h)',
                'towerify.stripe.plans.standard.monthly_price' => '400',
                'towerify.stripe.plans.standard.monthly_price_id' => 'price_1QeeA0DHRqrzgOLGmijDTdnj',
                'towerify.stripe.plans.standard.yearly_price' => null,
                'towerify.stripe.plans.standard.yearly_price_id' => null,
                'towerify.stripe.plans.standard.onetime_price' => null,
                'towerify.stripe.plans.standard.onetime_price_id' => null,

                'towerify.stripe.plans.premium.name' => 'Premium',
                'towerify.stripe.plans.premium.description' => null,
                'towerify.stripe.plans.premium.features' => 'Tout ce qui est dans Premium, CyberBuddy via Teams, SSO, Référentiels additionnels, 15 jours gratuits, Assistance par tickets (réponse sour 6h)',
                'towerify.stripe.plans.premium.monthly_price' => '600',
                'towerify.stripe.plans.premium.monthly_price_id' => 'price_1QeeAaDHRqrzgOLGBFZ7ms8R',
                'towerify.stripe.plans.premium.yearly_price' => null,
                'towerify.stripe.plans.premium.yearly_price_id' => null,
                'towerify.stripe.plans.premium.onetime_price' => null,
                'towerify.stripe.plans.premium.onetime_price_id' => null,

                'towerify.clickhouse.host' => '',
                'towerify.clickhouse.username' => '',
                'towerify.clickhouse.password' => '',
                'towerify.clickhouse.database' => '',

                'towerify.sendgrid.api' => 'https://api.sendgrid.com/v3/mail/send',
                'encrypted:towerify.sendgrid.api_key' => 'ER#lce&UYg1jjqfV_cQGMmDoFVpnNgr9T9BWc5mp2JvXIrVLN9n1Hs9AR+6MwdZTX6a6kO+nq8Z7tPSiq23q4PruzEYvgkdjZYXrGCtkbRkbv2Zh+7JndbEZxjcc=',

                'towerify.josianne.host' => 'clickhouse.apps.josiane.computablefacts.io',
                'towerify.josianne.username' => 'cywise',
                'encrypted:towerify.josianne.password' => 'GDr6#awPcu0NKbuR_G06I3zEp0mVx3PDo0lTH1xxpx+MNVimAVAQDfONbRT0=',
                'towerify.josianne.database' => '',
            ]);
        }
    }

    private function setupConfigInDb(array $configs)
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

    private function setupWave()
    {
        Role::updateOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ], [
            'description' => 'The admin user has full access to all features including the ability to access the admin panel.',
        ]);
        Role::updateOrCreate([
            'name' => 'registered',
            'guard_name' => 'web',
        ], [
            'description' => 'This is the default user role. If a user has this role they have created an account; however, they are not a subscriber.',
        ]);
        Plan::where('name', 'Essential')->update(['active' => false]); // Deal with legacy plan name
        Plan::updateOrCreate([
            'name' => config('towerify.stripe.plans.essential.name'),
        ], [
            'description' => config('towerify.stripe.plans.essential.description'),
            'features' => config('towerify.stripe.plans.essential.features'),
            'role_id' => Role::where('name', 'administrator')->where('guard_name', 'web')->firstOrFail()->id,
            'default' => 0,
            'monthly_price' => config('towerify.stripe.plans.essential.monthly_price'),
            'monthly_price_id' => config('towerify.stripe.plans.essential.monthly_price_id'),
            'yearly_price' => config('towerify.stripe.plans.essential.yearly_price'),
            'yearly_price_id' => config('towerify.stripe.plans.essential.yearly_price_id'),
            'onetime_price' => config('towerify.stripe.plans.essential.onetime_price'),
            'onetime_price_id' => config('towerify.stripe.plans.essential.onetime_price_id'),
        ]);
        Plan::updateOrCreate([
            'name' => config('towerify.stripe.plans.standard.name'),
        ], [
            'description' => config('towerify.stripe.plans.standard.description'),
            'features' => config('towerify.stripe.plans.standard.features'),
            'role_id' => Role::where('name', 'administrator')->where('guard_name', 'web')->firstOrFail()->id,
            'default' => 1,
            'monthly_price' => config('towerify.stripe.plans.standard.monthly_price'),
            'monthly_price_id' => config('towerify.stripe.plans.standard.monthly_price_id'),
            'yearly_price' => config('towerify.stripe.plans.standard.yearly_price'),
            'yearly_price_id' => config('towerify.stripe.plans.standard.yearly_price_id'),
            'onetime_price' => config('towerify.stripe.plans.standard.onetime_price'),
            'onetime_price_id' => config('towerify.stripe.plans.standard.onetime_price_id'),
        ]);
        Plan::updateOrCreate([
            'name' => config('towerify.stripe.plans.premium.name'),
        ], [
            'description' => config('towerify.stripe.plans.premium.description'),
            'features' => config('towerify.stripe.plans.premium.features'),
            'role_id' => Role::where('name', 'administrator')->where('guard_name', 'web')->firstOrFail()->id,
            'default' => 0,
            'monthly_price' => config('towerify.stripe.plans.premium.monthly_price'),
            'monthly_price_id' => config('towerify.stripe.plans.premium.monthly_price_id'),
            'yearly_price' => config('towerify.stripe.plans.premium.yearly_price'),
            'yearly_price_id' => config('towerify.stripe.plans.premium.yearly_price_id'),
            'onetime_price' => config('towerify.stripe.plans.premium.onetime_price'),
            'onetime_price_id' => config('towerify.stripe.plans.premium.onetime_price_id'),
        ]);
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

    private function setupTenants(): void
    {
        //
    }

    private function setupPermissions(): void
    {
        // Remove support for legacy permissions
        Permission::where('name', 'configure ssh connections')->delete();
        Permission::where('name', 'configure app permissions')->delete();
        Permission::where('name', 'configure user apps')->delete();
        Permission::where('name', 'deploy apps')->delete();
        Permission::where('name', 'launch apps')->delete();
        Permission::where('name', 'send invitations')->delete();

        // Create missing permissions
        foreach (Role::ROLES as $role => $permissions) {
            foreach ($permissions as $permission) {
                $perm = Permission::firstOrCreate(
                    ['name' => $permission],
                    [
                        'name' => $permission,
                        'guard_name' => 'web',
                    ]
                );
            }
        }
    }

    private function setupRoles(): void
    {
        // Create missing roles
        foreach (Role::ROLES as $role => $permissions) {
            /** @var Role $role */
            $role = Role::firstOrcreate([
                'name' => $role
            ]);
            foreach ($permissions as $permission) {
                $perm = Permission::where('name', $permission)->firstOrFail();
                $role->permissions()->syncWithoutDetaching($perm);
            }
        }
    }

    private function setupUsers(): void
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
        \App\Models\YnhOsqueryRule::query()->update(['enabled' => false]);

        foreach ($rules as $rule) {
            \App\Models\YnhOsqueryRule::updateOrCreate(['name' => $rule['name']], $rule);
        }
    }

    private function fillMissingOsqueryUids(): void
    {
        \App\Models\YnhOsquery::whereNull('columns_uid')
            ->chunk(1000, function (\Illuminate\Support\Collection $osquery) {
                $osquery->each(function (\App\Models\YnhOsquery $osquery) {
                    $osquery->columns_uid = \App\Models\YnhOsquery::computeColumnsUid($osquery->columns);
                    $osquery->save();
                });
            });
    }

    private function setupUserPromptsAndFrameworks(): void
    {
        \App\Models\Tenant::query()->chunkById(100, function ($tenants) {
            /** @var \App\Models\Tenant $tenant */
            foreach ($tenants as $tenant) {

                $oldestInTenant = User::query()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('created_at')
                    ->first();

                if ($oldestInTenant) {
                    User::init($oldestInTenant, true);
                }

                User::query()
                    ->where('tenant_id', $tenant->id)
                    ->when($oldestInTenant, fn($query) => $query->where('id', '<>', $oldestInTenant->id))
                    ->chunkById(100, function ($users) {
                        /** @var User $user */
                        foreach ($users as $user) {
                            User::init($user, false);
                        }
                    });
            }
        });

    }

    private function setupFrameworks(): void
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
        }
    }

    private function mitreAttckMatrix(): array
    {
        // https://github.com/bgenev/impulse-xdr/blob/main/managerd/main/helpers/data/mitre_matrix.json
        $path = database_path('seeders/mitre_matrix.json');
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
        $path = database_path('seeders/osquery.json');
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
