<?php

use App\Enums\OsqueryPlatformEnum;
use App\Models\User;
use App\Models\YnhOssecCheck;
use App\Models\YnhOssecPolicy;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryRule;
use App\Models\YnhServer;

function createUnixOssecRuleForAgentTest(int $uid = 50004): YnhOssecCheck
{
    $policy = YnhOssecPolicy::create([
        'uid' => 'cywise_ossec_unix',
        'name' => 'Cywise OSSEC Rules for Unix',
        'description' => 'Cywise OSSEC Rules for Unix',
        'references' => [],
        'requirements' => [],
    ]);

    return YnhOssecCheck::create([
        'ynh_ossec_policy_id' => $policy->id,
        'uid' => $uid,
        'title' => 'Check owner and permissions for /etc/passwd',
        'description' => 'Check /etc/passwd.',
        'rationale' => '',
        'impact' => '',
        'remediation' => '',
        'references' => [],
        'compliance' => [],
        'requirements' => [
            'rule_name' => 'Check owner and permissions for /etc/passwd',
            'match_type' => 'all',
            'references' => [],
            'rules' => [[
                'type' => 'file',
                'files' => ['/etc/passwd'],
                'expr' => null,
                'negate' => false,
            ]],
        ],
        'rule' => "[Check owner and permissions for /etc/passwd] [all] []\nf:/etc/passwd;",
    ]);
}

test('the Linux agent can fetch exactly one Unix OSSEC rule by uid', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->for($user, 'user')->create([
        'secret' => 'agent-secret',
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);
    $check = createUnixOssecRuleForAgentTest();

    $response = $this->getJson("/ossec-agent/{$server->secret}/rules/{$check->uid}");

    $response
        ->assertOk()
        ->assertJsonPath('uid', 50004)
        ->assertJsonPath('policy_uid', 'cywise_ossec_unix')
        ->assertJsonPath('title', 'Check owner and permissions for /etc/passwd')
        ->assertJsonPath('requirements.rule_name', 'Check owner and permissions for /etc/passwd')
        ->assertJsonStructure([
            'uid',
            'policy_uid',
            'title',
            'revision',
            'requirements' => [
                'rule_name',
                'match_type',
                'references',
                'rules',
                'cywise_link',
            ],
        ]);

    expect($response->json('revision'))->toMatch('/^[a-f0-9]{64}$/');
});

test('the OSSEC agent endpoint rejects an unknown server secret', function () {
    createUnixOssecRuleForAgentTest();

    $this->getJson('/ossec-agent/unknown/rules/50004')
        ->assertNotFound()
        ->assertJsonPath('message', 'Unknown server.');
});

test('the OSSEC agent endpoint only exposes rules from the Unix policy', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->for($user, 'user')->create([
        'secret' => 'agent-secret',
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);
    $policy = YnhOssecPolicy::create([
        'uid' => 'other_policy',
        'name' => 'Other policy',
        'description' => 'Other policy',
        'references' => [],
        'requirements' => [],
    ]);
    YnhOssecCheck::create([
        'ynh_ossec_policy_id' => $policy->id,
        'uid' => 50004,
        'title' => 'Other rule',
        'description' => '',
        'rationale' => '',
        'impact' => '',
        'remediation' => '',
        'references' => [],
        'compliance' => [],
        'requirements' => [],
        'rule' => 'f:/etc/passwd;',
    ]);

    $this->getJson("/ossec-agent/{$server->secret}/rules/50004")
        ->assertNotFound()
        ->assertJsonPath('message', 'Unknown Unix OSSEC rule.');
});

test('the Unix OSSEC endpoint rejects a Windows server', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->for($user, 'user')->create([
        'secret' => 'windows-agent-secret',
        'platform' => OsqueryPlatformEnum::WINDOWS,
    ]);
    createUnixOssecRuleForAgentTest();

    $this->getJson("/ossec-agent/{$server->secret}/rules/50004")
        ->assertStatus(422);
});

test('an OSSEC result is ingested through the existing osquery JSON pipeline', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->for($user, 'user')->create([
        'secret' => 'agent-secret',
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);
    $eventRule = YnhOsqueryRule::updateOrCreate([
        'name' => 'cywise_ossec_rule_result',
    ], [
        'description' => 'OSSEC result',
        'query' => 'SELECT * FROM osquery_info WHERE 1 = 0;',
        'version' => '1.0.0',
        'interval' => 86400,
        'snapshot' => true,
        'platform' => OsqueryPlatformEnum::ALL,
        'category' => 'security_check',
        'enabled' => true,
        'attck' => null,
        'is_ioc' => false,
        'score' => 0,
        'comments' => 'OSSEC result',
        'created_by' => null,
    ]);
    $event = [
        'row' => 0,
        'name' => 'cywise_ossec_rule_result',
        'hostIdentifier' => 'agent-linux',
        'calendarTime' => 'Thu Jul 23 04:44:12 2026 UTC',
        'unixTime' => 1784781852,
        'epoch' => 0,
        'counter' => 0,
        'numerics' => 0,
        'action' => 'snapshot',
        'columns' => [
            'policy_uid' => 'cywise_ossec_unix',
            'rule_uid' => '50004',
            'status' => 'passed',
            'duration_ms' => '183',
            'error' => '[]',
            'text' => 'OSSEC rule 50004 passed: the server is compliant.',
        ],
    ];

    $this->postJson("/logalert/{$server->secret}", [
        'name' => 'Monitor Cywise OSSEC Results',
        'file' => '/var/log/cywise/ossec-results.log',
        'date' => '2026-07-23T04:44:12Z',
        'hostname' => 'agent-linux',
        'lines' => [json_encode($event)],
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $stored = YnhOsquery::where('ynh_server_id', $server->id)
        ->where('name', 'cywise_ossec_rule_result')
        ->first();

    expect($stored)->not->toBeNull()
        ->and($stored->ynh_osquery_rule_id)->toBe($eventRule->id)
        ->and($stored->columns['rule_uid'])->toBe('50004')
        ->and($stored->columns['status'])->toBe('passed');
});
