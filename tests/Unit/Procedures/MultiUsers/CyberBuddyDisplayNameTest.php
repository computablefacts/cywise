<?php

use App\Models\AppConfig;
use Illuminate\Support\Facades\Blade;

it('falls back to CyberBuddy when no tenant display name is configured', function () {
    asTenant1User();

    expect(tenant_custom_text('CyberBuddy'))->toBe('CyberBuddy');
    expect(Blade::render('<span>{{ tenant_custom_text("CyberBuddy") }}</span>'))->toContain('CyberBuddy');
});

it('returns the tenant-specific display name when configured', function () {
    $user = tenant1User();

    AppConfig::create([
        'is_encrypted' => false,
        'key' => tenant_custom_text_key('CyberBuddy', $user->tenant_id),
        'value' => 'CyberGuide',
    ]);

    asTenant1User();

    expect(tenant_custom_text('CyberBuddy'))->toBe('CyberGuide');
    expect(Blade::render('<span>{{ tenant_custom_text("CyberBuddy") }}</span>'))->toContain('CyberGuide');
});

it('does not leak one tenant display name to another tenant', function () {
    $tenant1User = tenant1User();

    AppConfig::create([
        'is_encrypted' => false,
        'key' => tenant_custom_text_key('CyberBuddy', $tenant1User->tenant_id),
        'value' => 'CyberGuide',
    ]);

    asTenant2User();

    expect(tenant_custom_text('CyberBuddy'))->toBe('CyberBuddy');
    expect(Blade::render('<span>{{ tenant_custom_text("CyberBuddy") }}</span>'))->toContain('CyberBuddy');
});

it('can customize another arbitrary UI text through the generic helper', function () {
    $user = tenant1User();

    AppConfig::create([
        'is_encrypted' => false,
        'key' => tenant_custom_text_key('Click here to launch', $user->tenant_id),
        'value' => 'Open your assistant',
    ]);

    asTenant1User();

    expect(tenant_custom_text('Click here to launch'))->toBe('Open your assistant');
    expect(Blade::render('<span>{{ tenant_custom_text("Click here to launch") }}</span>'))
        ->toContain('Open your assistant');
});

it('does not inject tenant display text keys into laravel config overrides', function () {
    $user = tenant1User();
    $displayTextKey = tenant_custom_text_key('CyberBuddy', $user->tenant_id);
    $databasePath = tempnam(sys_get_temp_dir(), 'app-config-override-test-');
    $pdo = new PDO('sqlite:' . $databasePath);

    $pdo->exec('CREATE TABLE app_config (id INTEGER PRIMARY KEY AUTOINCREMENT, key VARCHAR(255) NOT NULL UNIQUE, value VARCHAR(4096) NULL, is_encrypted TINYINT NOT NULL DEFAULT 0, created_at DATETIME NULL, updated_at DATETIME NULL)');
    $pdo->exec("INSERT INTO app_config (key, value, is_encrypted) VALUES ('app.name', 'Cywise Test', 0)");
    $pdo->exec("INSERT INTO app_config (key, value, is_encrypted) VALUES ('{$displayTextKey}', 'CyberGuide', 0)");
    $pdo = null;

    $defaultConnection = config('database.default');
    $sqliteDatabase = config('database.connections.sqlite.database');

    try {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $databasePath);
        config()->set('app.name', 'Laravel');
        config()->set($displayTextKey, null);

        expect(app_config_override())->toBe(['loaded' => true]);
        expect(config('app.name'))->toBe('Cywise Test');
        expect(config($displayTextKey))->toBeNull();
    } finally {
        config()->set('database.default', $defaultConnection);
        config()->set('database.connections.sqlite.database', $sqliteDatabase);
        @unlink($databasePath);
    }
});

it('builds a readable slug-based app config key', function () {
    expect(tenant_custom_text_key('CyberBuddy', 12))
        ->toBe('tenant_display_text.12.cyberbuddy');
    expect(tenant_custom_text_key('Click here to launch', 12))
        ->toBe('tenant_display_text.12.click-here-to-launch');
});
