<?php

namespace App\Models;

class Role extends \Spatie\Permission\Models\Role
{
    const ADMIN = 'admin';
    const ADMINISTRATOR = 'administrator';
    const LIMITED_ADMINISTRATOR = 'limited administrator';
    const BASIC_END_USER = 'basic end user';
    const CYBERBUDDY_ONLY = 'cyberbuddy only';
    const CYBERBUDDY_ADMIN = 'cyberbuddy admin';
    const ROLES = [
        self::ADMIN => [

            // Legacy
            Permission::LIST_SERVERS,
            Permission::MANAGE_SERVERS,
            Permission::USE_AGENTS,
            Permission::LIST_APPS,
            Permission::MANAGE_APPS,
            Permission::USE_YUNOHOST,
            Permission::LIST_USERS,
            Permission::MANAGE_USERS,
            Permission::BUY_STUFF,
            Permission::USE_MARKETPLACE,
            Permission::USE_ADVERSARY_METER,
            Permission::USE_VULNERABILITY_SCANNER,
            Permission::USE_HONEYPOTS,
            Permission::USE_CYBER_BUDDY,

            // Add missing permissions after upgrade to Laravel 11.0+ and Vanilo 4.0+
            'list tax rates',
            'view tax rates',
            'list tax categories',
            'view tax categories',

            // New
            Permission::VIEW_OVERVIEW,
            Permission::VIEW_METRICS,
            Permission::VIEW_EVENTS,

            Permission::VIEW_ASSETS,
            Permission::VIEW_VULNERABILITIES,
            Permission::VIEW_SERVICE_PROVIDER_DELEGATION,

            Permission::VIEW_AGENTS,
            Permission::VIEW_SECURITY_RULES,

            Permission::VIEW_HONEYPOTS,
            Permission::VIEW_ATTACKERS,
            Permission::VIEW_IP_BLACKLIST,

            Permission::VIEW_HARDENING,
            Permission::VIEW_FRAMEWORKS,
            Permission::VIEW_AI_WRITER,
            Permission::VIEW_CYBERBUDDY,
            Permission::VIEW_CONVERSATIONS,
            Permission::VIEW_COLLECTIONS,
            Permission::VIEW_DOCUMENTS,
            Permission::VIEW_TABLES,
            Permission::VIEW_CHUNKS,
            Permission::VIEW_PROMPTS,

            Permission::VIEW_DESKTOP,
            Permission::VIEW_SERVERS,
            Permission::VIEW_APPLICATIONS,
            Permission::VIEW_DOMAINS,
            Permission::VIEW_BACKUPS,
            Permission::VIEW_INTERDEPENDENCIES,
            Permission::VIEW_TRACES,

            Permission::VIEW_PRODUCTS,
            Permission::VIEW_CART,
            Permission::VIEW_ORDERS,

            Permission::VIEW_USERS,
            Permission::VIEW_INVITATIONS,
            Permission::VIEW_PLANS,
            Permission::VIEW_MY_SUBSCRIPTION,
            Permission::VIEW_DOCUMENTATION,
            Permission::VIEW_TERMS,
            Permission::VIEW_RESET_PASSWORD,
        ],
        self::ADMINISTRATOR => [

            // Legacy
            Permission::LIST_SERVERS,
            Permission::MANAGE_SERVERS,
            Permission::USE_AGENTS,
            Permission::LIST_APPS,
            Permission::MANAGE_APPS,
            Permission::USE_YUNOHOST,
            Permission::LIST_USERS,
            Permission::MANAGE_USERS,
            Permission::USE_ADVERSARY_METER,
            Permission::USE_VULNERABILITY_SCANNER,
            Permission::USE_HONEYPOTS,
            Permission::USE_CYBER_BUDDY,

            // Add missing permissions after upgrade to Laravel 11.0+ and Vanilo 4.0+
            'list tax rates',
            'view tax rates',
            'list tax categories',
            'view tax categories',

            // New
            Permission::VIEW_OVERVIEW,
            Permission::VIEW_METRICS,
            Permission::VIEW_EVENTS,

            Permission::VIEW_ASSETS,
            Permission::VIEW_VULNERABILITIES,
            Permission::VIEW_SERVICE_PROVIDER_DELEGATION,

            Permission::VIEW_AGENTS,
            Permission::VIEW_SECURITY_RULES,

            Permission::VIEW_HONEYPOTS,
            Permission::VIEW_ATTACKERS,
            Permission::VIEW_IP_BLACKLIST,

            Permission::VIEW_HARDENING,
            Permission::VIEW_FRAMEWORKS,
            Permission::VIEW_AI_WRITER,
            Permission::VIEW_CYBERBUDDY,
            Permission::VIEW_CONVERSATIONS,
            Permission::VIEW_COLLECTIONS,
            Permission::VIEW_DOCUMENTS,
            Permission::VIEW_TABLES,
            Permission::VIEW_CHUNKS,
            Permission::VIEW_PROMPTS,

            Permission::VIEW_DESKTOP,
            Permission::VIEW_SERVERS,
            Permission::VIEW_APPLICATIONS,
            Permission::VIEW_DOMAINS,
            Permission::VIEW_BACKUPS,
            Permission::VIEW_INTERDEPENDENCIES,
            Permission::VIEW_TRACES,

            Permission::VIEW_USERS,
            Permission::VIEW_INVITATIONS,
            Permission::VIEW_PLANS,
            Permission::VIEW_MY_SUBSCRIPTION,
            Permission::VIEW_DOCUMENTATION,
            Permission::VIEW_TERMS,
            Permission::VIEW_RESET_PASSWORD,
        ],
        self::LIMITED_ADMINISTRATOR => [

            // Legacy
            Permission::LIST_SERVERS,
            Permission::LIST_APPS,
            Permission::MANAGE_APPS,
            Permission::USE_YUNOHOST,
            Permission::LIST_USERS,
            Permission::MANAGE_USERS,
            Permission::USE_ADVERSARY_METER,
            Permission::USE_VULNERABILITY_SCANNER,
            Permission::USE_HONEYPOTS,
            Permission::USE_CYBER_BUDDY,

            // New
            Permission::VIEW_OVERVIEW,
            Permission::VIEW_METRICS,
            Permission::VIEW_EVENTS,

            Permission::VIEW_ASSETS,
            Permission::VIEW_VULNERABILITIES,
            Permission::VIEW_SERVICE_PROVIDER_DELEGATION,

            Permission::VIEW_AGENTS,
            Permission::VIEW_SECURITY_RULES,

            Permission::VIEW_HONEYPOTS,
            Permission::VIEW_ATTACKERS,
            Permission::VIEW_IP_BLACKLIST,

            Permission::VIEW_HARDENING,
            Permission::VIEW_FRAMEWORKS,
            Permission::VIEW_AI_WRITER,
            Permission::VIEW_CYBERBUDDY,
            Permission::VIEW_CONVERSATIONS,
            Permission::VIEW_COLLECTIONS,
            Permission::VIEW_DOCUMENTS,
            Permission::VIEW_TABLES,
            Permission::VIEW_CHUNKS,
            Permission::VIEW_PROMPTS,

            Permission::VIEW_DESKTOP,
            Permission::VIEW_SERVERS,
            Permission::VIEW_APPLICATIONS,
            Permission::VIEW_DOMAINS,
            Permission::VIEW_BACKUPS,
            Permission::VIEW_INTERDEPENDENCIES,
            Permission::VIEW_TRACES,

            Permission::VIEW_USERS,
            Permission::VIEW_INVITATIONS,
            // Permission::VIEW_PLANS,
            // Permission::VIEW_MY_SUBSCRIPTION,
            Permission::VIEW_DOCUMENTATION,
            Permission::VIEW_TERMS,
            Permission::VIEW_RESET_PASSWORD,
        ],
        self::BASIC_END_USER => [

            // Legacy
            Permission::LIST_APPS,

            // New
            Permission::VIEW_DESKTOP,
        ],
        self::CYBERBUDDY_ONLY => [

            // Legacy
            Permission::USE_CYBER_BUDDY,
            Permission::USE_ADVERSARY_METER,

            // New
            Permission::VIEW_CYBERBUDDY,
        ],
        self::CYBERBUDDY_ADMIN => [

            // Legacy
            Permission::USE_CYBER_BUDDY,
            Permission::USE_ADVERSARY_METER,

            // ISSP
            Permission::VIEW_HARDENING,
            Permission::VIEW_FRAMEWORKS,
            Permission::VIEW_AI_WRITER,
            Permission::VIEW_CYBERBUDDY,
            Permission::VIEW_CONVERSATIONS,
            Permission::VIEW_COLLECTIONS,
            Permission::VIEW_DOCUMENTS,
            Permission::VIEW_TABLES,
            Permission::VIEW_CHUNKS,
            Permission::VIEW_PROMPTS,

            // Settings
            Permission::VIEW_USERS,
            Permission::VIEW_INVITATIONS,
            Permission::VIEW_DOCUMENTATION,
        ],
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
