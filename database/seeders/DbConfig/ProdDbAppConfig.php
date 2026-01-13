<?php

namespace Database\Seeders\DbConfig;

class ProdDbAppConfig implements DbAppConfigInterface
{
    public function getParams(): array
    {
        return [
            'towerify.website' => 'https://www.cywise.io',

            'towerify.freshdesk.widget_id' => '44000004635',
            'towerify.freshdesk.to_email' => 'support@computablefacts.freshdesk.com',
            'towerify.freshdesk.from_email' => 'support@cywise.io',

            'towerify.reports.url' => 'https://reports.cywise.io',
            'towerify.reports.api' => 'https://reports.cywise.io/api/v1',
            'towerify.reports.api_username' => 'admin',
            'encrypted:towerify.reports.api_password' => 'LTF3#G9jB4skruym_pgXeZrDViPcfevUX+PxiRzn9VgHaypMRLK5nV7H1e98=',

            'array:towerify.adversarymeter.ip_addresses' => '62.210.90.152',
            'towerify.adversarymeter.api' => 'https://prod.sentinel-api-rq.apps.sentinel-api.towerify.io/api/v1',
            'towerify.adversarymeter.api_username' => 'sentinel-admin',
            'encrypted:towerify.adversarymeter.api_password' => '25wNPGRVJD6ZXgmP_AICYQmVP4KbC2Hfz8BhT1VzVvAFadzOaz8xL57M9P9g=',
            'towerify.adversarymeter.drop_scan_events_after_x_minutes' => '1440',
            'towerify.adversarymeter.drop_discovery_events_after_x_minutes' => '60',
            'towerify.adversarymeter.days_between_scans' => '5',

            'towerify.cyberbuddy.api' => 'https://prod.generic-rag.dev02.towerify.io',
            'towerify.cyberbuddy.api_username' => 'gr',
            'encrypted:towerify.cyberbuddy.api_password' => 'J8l!aJd8EI7iwkgz_txIvvdR02JEPqOaOxK9N7A==',

            'towerify.deepseek.api' => 'https://api.deepseek.com/v1',
            'encrypted:towerify.deepseek.api_key' => 'It&C5lRsI!Dq1IsX_ea+wX+Mz22wW95wY1cjq56mDp76lxfeLNrz40RSfe5n9eqflr8lz/TyDsmcqnygh',

            'towerify.deepinfra.api' => 'https://api.deepinfra.com/v1/openai',
            'encrypted:towerify.deepinfra.api_key' => '2sb?kbsVy4yE3gFw_xNKSEzcwSsF2E8gHmym8okt84QGYCy9T9Sp0e7u3PvkTO/C9z1qEebGyHaJ8Sj3V',

            'towerify.gemini.api' => 'https://generativelanguage.googleapis.com/v1beta/openai',
            'encrypted:towerify.gemini.api_key' => 'bYg2zlwYpWlULSJ9_boRGCkKbpzTr5NHeZ9ZBqaCkeaCmK2/lpr8mFLCuPcUNvKPlesCldGWo4L7Mb9rc',

            'array:towerify.telescope.whitelist.usernames' => 'csavelief,engineering,pbrisacier,pduteil,pduteil+dev',
            'array:towerify.telescope.whitelist.domains' => 'computablefacts.com,hdwsec.fr,mncc.fr',

            'array:towerify.performa.whitelist.usernames' => 'csavelief,engineering,pbrisacier,pduteil,pduteil+dev',
            'array:towerify.performa.whitelist.domains' => 'computablefacts.com,hdwsec.fr,mncc.fr',

            'towerify.openai.api' => 'https://api.openai.com/v1',
            'encrypted:towerify.openai.api_key' => 'Tg2yWb33U2tIItmd_IU0txV/5KxNv9+bwBnqkMVYujizOT2ErSz59maW2/r71cKbi5KxikqLZ5D6U+AdreucHfXdxd4LN7ZgX8LGI/V2J1qgagPa/xEValIH34MoGeJEIUN11zaKIyBpp2YYV++iQAkGgI9wdjcYGWtr6EjZZELv9tw8OiSrW1GzEf8O7Z86bHiE7DYqxX//3caIzba7ASNMbdAEclXAnMjZa5JstmPEiyU4U6+B5zCTc4ok=',

            'encrypted:towerify.scrapfly.api_key' => '!jr0dNpdZSWuc5cg_BVlBZwoXazqe4LuXvFWaO6+Vnv/wYQx8751JOkXHyLT+4pPsJhbFHC55pwpdhgMI',

            'encrypted:towerify.scraperapi.api_key' => 'MQK32#KsaMczwIRb_0sY+5xNWzDIUWU9ZS0lGlsToaed8w1Yf2OaTmysiUbVS8vgGLgWcwVkfgL1LIHPx',

            'encrypted:towerify.stripe.key' => 'emqKPsH94Gp4!ziy_fBp0naOHlnV28A5fDfY8hWrQy6w9QGUja3fZeIXG4n6214U4OIP+eFJQRwRO5qJ3sFJvM9wMBuwUhpXnMLkT2gpRUG1kdzHVIaGSQeq/6bhwJTF3xNwDTDcvQf70F73uldge+VVroUKdqFgKZiClNQ==',
            'encrypted:towerify.stripe.secret' => 'r0ZRSP5WHOFJ?H8z_031Fm8fYryEEHjmkax3GINurDzaYtrE0bpWVIAI0y8zxlwqDxoPRKEJ5y0W6Dq+wJp3JEA5FoL+NuZ42bN5Jd8blDdwJqeSqyKsTPWZKmr3sdpNUinLRxf4XxFRRXMpYOCRDOzqrk0qZeOw4HLRfkg==',
            'encrypted:wave.stripe.publishable_key' => 'emqKPsH94Gp4!ziy_fBp0naOHlnV28A5fDfY8hWrQy6w9QGUja3fZeIXG4n6214U4OIP+eFJQRwRO5qJ3sFJvM9wMBuwUhpXnMLkT2gpRUG1kdzHVIaGSQeq/6bhwJTF3xNwDTDcvQf70F73uldge+VVroUKdqFgKZiClNQ==',
            'encrypted:wave.stripe.secret_key' => 'r0ZRSP5WHOFJ?H8z_031Fm8fYryEEHjmkax3GINurDzaYtrE0bpWVIAI0y8zxlwqDxoPRKEJ5y0W6Dq+wJp3JEA5FoL+NuZ42bN5Jd8blDdwJqeSqyKsTPWZKmr3sdpNUinLRxf4XxFRRXMpYOCRDOzqrk0qZeOw4HLRfkg==',
            'encrypted:devdojo.billing.keys.stripe.publishable_key' => 'emqKPsH94Gp4!ziy_fBp0naOHlnV28A5fDfY8hWrQy6w9QGUja3fZeIXG4n6214U4OIP+eFJQRwRO5qJ3sFJvM9wMBuwUhpXnMLkT2gpRUG1kdzHVIaGSQeq/6bhwJTF3xNwDTDcvQf70F73uldge+VVroUKdqFgKZiClNQ==',
            'encrypted:devdojo.billing.keys.stripe.secret_key' => 'r0ZRSP5WHOFJ?H8z_031Fm8fYryEEHjmkax3GINurDzaYtrE0bpWVIAI0y8zxlwqDxoPRKEJ5y0W6Dq+wJp3JEA5FoL+NuZ42bN5Jd8blDdwJqeSqyKsTPWZKmr3sdpNUinLRxf4XxFRRXMpYOCRDOzqrk0qZeOw4HLRfkg==',

            'towerify.stripe.plans.essential.name' => 'Essentiel',
            'towerify.stripe.plans.essential.description' => 'L\'offre la plus adaptée aux TPE.',
            'towerify.stripe.plans.essential.features' => 'Scan de vulnérabilités, Honeypots pré-configurés, Adresses emails internes compromises, Charte informatique, Cyberbuddy, 15 jours gratuits, Assistance par tickets (réponse sous 48h)',
            'towerify.stripe.plans.essential.monthly_price' => '90',
            'towerify.stripe.plans.essential.monthly_price_id' => 'price_1S6SqODHRqrzgOLGBGYEnrDd',
            'towerify.stripe.plans.essential.yearly_price' => '900',
            'towerify.stripe.plans.essential.yearly_price_id' => 'price_1S6SrODHRqrzgOLGvt9xmhXM',
            'towerify.stripe.plans.essential.onetime_price' => null,
            'towerify.stripe.plans.essential.onetime_price_id' => null,

            'towerify.stripe.plans.standard.name' => 'Standard',
            'towerify.stripe.plans.standard.description' => 'L\'offre la plus adaptée aux PME.',
            'towerify.stripe.plans.standard.features' => 'Tout ce qui est dans Essentiel, Agent, Honeypots sur des domaines spécifiques, Adresses emails de l\'écosystème compromises, Règles de Hardening par référentiel Cyber, PSSI (Politique de Sécurité des Systèmes d\'Information), 15 jours gratuits, Assistance par tickets (réponse sous 24h)',
            'towerify.stripe.plans.standard.monthly_price' => '270',
            'towerify.stripe.plans.standard.monthly_price_id' => 'price_1S6SxODHRqrzgOLG2qXK0Ygm',
            'towerify.stripe.plans.standard.yearly_price' => '2700',
            'towerify.stripe.plans.standard.yearly_price_id' => 'price_1S6Sy7DHRqrzgOLGhPQ7RQSc',
            'towerify.stripe.plans.standard.onetime_price' => null,
            'towerify.stripe.plans.standard.onetime_price_id' => null,

            'towerify.stripe.plans.premium.name' => 'Premium',
            'towerify.stripe.plans.premium.description' => 'L\'offre la plus adaptée aux ETI et Grands Groupes.',
            'towerify.stripe.plans.premium.features' => 'Tout ce qui est dans Standard, CyberBuddy via Teams, SSO (Single Sign-On), Référentiels additionnels, 15 jours gratuits, Assistance par tickets (réponse sous 6h)',
            'towerify.stripe.plans.premium.monthly_price' => '810',
            'towerify.stripe.plans.premium.monthly_price_id' => 'price_1S6T7qDHRqrzgOLG1FZrhRlZ',
            'towerify.stripe.plans.premium.yearly_price' => '8100',
            'towerify.stripe.plans.premium.yearly_price_id' => 'price_1S6T8aDHRqrzgOLGzVZgXpP7',
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

            'mail.mailers.mailcoach.domain' => 'cywiseapp.mailcoach.app',
            'encrypted:mail.mailers.mailcoach.token' => 'm9YPqLakWErKGBs#_GTJmLbOadKQEvkdeFwfdYmXfX7BMkF2CsSh8stuIBhsQ88b6z//nzI+BiMf3mS6XW86nYxcZjaKF2e6eEbD74g==',
        ];
    }
}
