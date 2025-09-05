<?php

namespace Database\Seeders\DbConfig;

class DevDbAppConfig implements DbAppConfigInterface
{
    public function getParams(): array
    {
        return [
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

            'array:towerify.telescope.whitelist.usernames' => 'demo,csavelief,engineering,pbrisacier,pduteil,pduteil+dev',
            'array:towerify.telescope.whitelist.domains' => 'towerify.io,computablefacts.com,hdwsec.fr,mncc.fr',

            'array:towerify.performa.whitelist.usernames' => 'csavelief,engineering,pbrisacier,pduteil,pduteil+dev',
            'array:towerify.performa.whitelist.domains' => 'computablefacts.com,hdwsec.fr,mncc.fr',

            'towerify.openai.api' => 'https://api.openai.com/v1',
            'encrypted:towerify.openai.api_key' => '6dbLxUYdsOQiFRcp_xgdMdae7nL5B8uULLU+G3QpXRga0i0qQwPIurAV7T3t/b7WtVn/9etjY6/tFavJfwt9dl5NW2ZHvSIVgKXukMNqQ6k/ZcH1qh9flUu9s2BCW7/YOQJ3hYNAxEanCVglXqazkirUbFwiO5wcsSvg99N0nCoRYVAJNj3/loI8ADfREkYRKEIENyoGNvv7hpPQSvRaGqhE7FR7K7PPzkt84y3FbcR/YJk4IlCRJ7TC1mzA=',

            'encrypted:towerify.scrapfly.api_key' => '2xXcLYRIKdjFlDL&_fhu7isCCrP20g0iuchgOj/CJIIdGKVq/fpk7lSkcDJiQxLcPkZHfjz5/pf5S3AqI',

            'encrypted:towerify.scraperapi.api_key' => 'f3xkpO94jZGaAJE3_yMyYN+Ub8Zhp63k2i7qsbuHKQ2FRbQdh6/agBlfI7hodzDLH9kbWkcMRJFobctTZ',

            'towerify.stripe.key' => '',
            'towerify.stripe.secret' => '',

            'towerify.stripe.plans.essential.name' => 'Essentiel',
            'towerify.stripe.plans.essential.description' => null,
            'towerify.stripe.plans.essential.features' => 'Scan de vulnérabilités, Honeypots pré-configurés, Adresses emails internes compromises, Charte informatique, Cyberbuddy, 15 jours gratuits, Assistance par tickets (réponse sous 48h)',
            'towerify.stripe.plans.essential.monthly_price' => '150',
            'towerify.stripe.plans.essential.monthly_price_id' => 'dummy_price_essentiel',
            'towerify.stripe.plans.essential.yearly_price' => null,
            'towerify.stripe.plans.essential.yearly_price_id' => null,
            'towerify.stripe.plans.essential.onetime_price' => null,
            'towerify.stripe.plans.essential.onetime_price_id' => null,

            'towerify.stripe.plans.standard.name' => 'Standard',
            'towerify.stripe.plans.standard.description' => null,
            'towerify.stripe.plans.standard.features' => 'Tout ce qui est dans Essentiel, Agent, Honeypots sur des domaines spécifiques, Adresses emails de l\'écosystème compromises, Règles de Hardening par référentiel Cyber, PSSI, 15 jours gratuits, Assistance par tickets (réponse sous 24h)',
            'towerify.stripe.plans.standard.monthly_price' => '400',
            'towerify.stripe.plans.standard.monthly_price_id' => 'dummy_price_standard',
            'towerify.stripe.plans.standard.yearly_price' => null,
            'towerify.stripe.plans.standard.yearly_price_id' => null,
            'towerify.stripe.plans.standard.onetime_price' => null,
            'towerify.stripe.plans.standard.onetime_price_id' => null,

            'towerify.stripe.plans.premium.name' => 'Premium',
            'towerify.stripe.plans.premium.description' => null,
            'towerify.stripe.plans.premium.features' => 'Tout ce qui est dans Standard, CyberBuddy via Teams, SSO, Référentiels additionnels, 15 jours gratuits, Assistance par tickets (réponse sous 6h)',
            'towerify.stripe.plans.premium.monthly_price' => '600',
            'towerify.stripe.plans.premium.monthly_price_id' => 'dummy_price_premium',
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

            'mail.mailers.mailcoach.domain' => 'cywiseapp.mailcoach.app',
            'encrypted:mail.mailers.mailcoach.token' => 'f4agCN2VzjXR47a2_VR/fo3UA7KfHG2ZkyNEO3j/xwozU6t7IdHAT9uaA6anNmHC/u2/vQBdZZ67l5UNWepmHZuXjZk18pDRhMGdjfg==',
        ];
    }
}
