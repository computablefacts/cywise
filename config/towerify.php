<?php

return [
    'website' => env('WEBSITE_URL'),
    'freshdesk' => [
        'widget_id' => env('FRESHDESK_ID'),
        'to_email' => env('FRESHDESK_TO_EMAIL'),
        'from_email' => env('FRESHDESK_FROM_EMAIL'),
    ],
    'reports' => [
        'url' => env('REPORTS_URL'),
        'api' => env('REPORTS_API'),
        'api_username' => env('REPORTS_API_USERNAME'),
        'api_password' => env('REPORTS_API_PASSWORD'),
    ],
    'hasher' => [
        'nonce' => env('HASHER_NONCE'),
    ],
    'adversarymeter' => [
        'ip_addresses' => explode(',', env('AM_IP_ADDRESSES')),
        'api' => env('AM_API'),
        'api_username' => env('AM_API_USERNAME'),
        'api_password' => env('AM_API_PASSWORD'),
        'drop_scan_events_after_x_minutes' => env('DROP_SCAN_EVENTS_AFTER_X_MINUTES', 24 * 60),
        'drop_discovery_events_after_x_minutes' => env('DROP_DISCOVERY_EVENTS_AFTER_X_MINUTES', 60),
        'days_between_scans' => env('DAYS_BETWEEN_SCANS', 5),
    ],
    'cyberbuddy' => [
        'api' => env('CB_API'),
        'api_username' => env('CB_API_USERNAME'),
        'api_password' => env('CB_API_PASSWORD'),
    ],
    'deepseek' => [
        'api' => env('DEEPSEEK_API', 'https://api.deepseek.com/v1'),
        'api_key' => env('DEEPSEEK_API_KEY'),
    ],
    'deepinfra' => [
        'api' => env('DEEPINFRA_API', 'https://api.deepinfra.com/v1/openai'),
        'api_key' => env('DEEPINFRA_API_KEY'),
    ],
    'gemini' => [
        'api' => env('GEMINI_API', 'https://generativelanguage.googleapis.com/v1beta/openai'),
        'api_key' => env('GEMINI_API_KEY'),
    ],
    'telescope' => [
        'whitelist' => [
            'usernames' => explode(',', env('TELESCOPE_WHITELIST_USERNAMES')),
            'domains' => explode(',', env('TELESCOPE_WHITELIST_DOMAINS')),
        ],
    ],
    'performa' => [
        'whitelist' => [
            'usernames' => explode(',', env('PERFORMA_WHITELIST_USERNAMES', env('TELESCOPE_WHITELIST_USERNAMES'))),
            'domains' => explode(',', env('PERFORMA_WHITELIST_DOMAINS', env('TELESCOPE_WHITELIST_DOMAINS'))),
        ],
    ],
    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'username' => env('ADMIN_USERNAME'),
        'password' => env('ADMIN_PASSWORD'),
    ],
    'openai' => [
        'api' => env('OPENAI_API', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
    ],
    'scrapfly' => [
        'api_key' => env('SCRAPFLY_API_KEY'),
    ],
    'scraperapi' => [
        'api_key' => env('SCRAPERAPI_API_KEY'),
    ],
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'plans' => [
            'essential' => [
                'name' => null,
                'description' => null,
                'features' => '',
                'monthly_price' => null,
                'monthly_price_id' => null,
                'yearly_price' => null,
                'yearly_price_id' => null,
                'onetime_price' => null,
                'onetime_price_id' => null,
            ],
            'standard' => [
                'name' => null,
                'description' => null,
                'features' => '',
                'monthly_price' => null,
                'monthly_price_id' => null,
                'yearly_price' => null,
                'yearly_price_id' => null,
                'onetime_price' => null,
                'onetime_price_id' => null,
            ],
            'premium' => [
                'name' => null,
                'description' => null,
                'features' => '',
                'monthly_price' => null,
                'monthly_price_id' => null,
                'yearly_price' => null,
                'yearly_price_id' => null,
                'onetime_price' => null,
                'onetime_price_id' => null,
            ],
        ],
    ],
    'clickhouse' => [
        'host' => env('CH_HOST'),
        'username' => env('CH_USERNAME'),
        'password' => env('CH_PASSWORD'),
        'database' => env('CH_DATABASE'),
    ],
    'sendgrid' => [
        'api' => env('SENDGRID_API'),
        'api_key' => env('SENDGRID_API_KEY'),
    ],
    'josianne' => [
        'host' => env('JOSIANNE_HOST'),
        'username' => env('JOSIANNE_USERNAME'),
        'password' => env('JOSIANNE_PASSWORD'),
        'database' => env('JOSIANNE_DATABASE'),
    ],
];
