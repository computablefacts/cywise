<?php

namespace Database\Seeders\DbConfig;

class LocalDbAppConfig implements DbAppConfigInterface
{
    public function getParams(): array
    {
        return [
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

            'array:towerify.telescope.whitelist.usernames' => 'csavelief,engineering,pbrisacier,pduteil,pduteil+dev',
            'array:towerify.telescope.whitelist.domains' => 'computablefacts.com,hdwsec.fr,mncc.fr',

            'array:towerify.performa.whitelist.usernames' => 'csavelief,engineering,pbrisacier,pduteil,pduteil+dev',
            'array:towerify.performa.whitelist.domains' => 'computablefacts.com,hdwsec.fr,mncc.fr',

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

            'mail.mailers.mailcoach.domain' => 'cywiseapp.mailcoach.app',
            // 'encrypted:mail.mailers.mailcoach.token' => '',
        ];
    }
}
