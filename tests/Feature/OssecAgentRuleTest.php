<?php

use App\Enums\OsqueryPlatformEnum;
use App\Models\User;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryRule;
use App\Models\YnhOssecCheck;
use App\Models\YnhOssecPolicy;
use App\Models\YnhServer;

function createOssecRuleForAgentTest(
    int $uid = 50004,
    string $policyUid = 'cywise_ossec_unix',
    string $policyName = 'Cywise OSSEC Rules for Unix',
): YnhOssecCheck {
    $policy = YnhOssecPolicy::firstOrCreate([
        'uid' => $policyUid,
    ], [
        'name' => $policyName,
        'description' => $policyName,
        'references' => [],
        'requirements' => [],
    ]);

    return YnhOssecCheck::create([
        'ynh_ossec_policy_id' => $policy->id,
        'uid' => $uid,
        'title' => "Check {$uid}",
        'description' => 'Check /etc/passwd.',
        'rationale' => '',
        'impact' => '',
        'remediation' => '',
        'references' => [],
        'compliance' => [],
        'requirements' => [
            'rule_name' => "Check {$uid}",
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
    $check = createOssecRuleForAgentTest();

    $response = $this->getJson("/ossec-agent/{$server->secret}/rules/{$check->uid}");

    $response
        ->assertOk()
        ->assertJsonPath('uid', 50004)
        ->assertJsonPath('policy_uid', 'cywise_ossec_unix')
        ->assertJsonPath('title', 'Check 50004')
        ->assertJsonPath('requirements.rule_name', 'Check 50004')
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

test('the Linux agent can fetch every rule from an OSSEC policy', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->for($user, 'user')->create([
        'secret' => 'agent-secret',
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);
    createOssecRuleForAgentTest(50005);
    createOssecRuleForAgentTest(50004);

    $response = $this->getJson(
        "/ossec-agent/{$server->secret}/policies/cywise_ossec_unix/rules",
    );

    $response
        ->assertOk()
        ->assertJsonPath('uid', 'cywise_ossec_unix')
        ->assertJsonPath('name', 'Cywise OSSEC Rules for Unix')
        ->assertJsonCount(2, 'rules')
        ->assertJsonPath('rules.0.uid', 50004)
        ->assertJsonPath('rules.1.uid', 50005)
        ->assertJsonPath('rules.0.policy_uid', 'cywise_ossec_unix')
        ->assertJsonPath('rules.1.policy_uid', 'cywise_ossec_unix')
        ->assertJsonStructure([
            'uid',
            'name',
            'revision',
            'rules' => [[
                'uid',
                'policy_uid',
                'title',
                'revision',
                'requirements',
            ]],
        ]);

    expect($response->json('revision'))->toMatch('/^[a-f0-9]{64}$/');
});

test('the agent can fetch another OSSEC policy compatible with its platform', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->for($user, 'user')->create([
        'secret' => 'centos-agent-secret',
        'platform' => OsqueryPlatformEnum::CENTOS,
    ]);
    createOssecRuleForAgentTest(
        uid: 60004,
        policyUid: 'cywise_ossec_centos',
        policyName: 'Cywise OSSEC Rules for CentOS',
    );

    $this->getJson(
        "/ossec-agent/{$server->secret}/policies/cywise_ossec_centos/rules",
    )
        ->assertOk()
        ->assertJsonPath('uid', 'cywise_ossec_centos')
        ->assertJsonPath('rules.0.uid', 60004);
});

test('the agent only exposes tenant policies belonging to its server', function () {
    $serverOwner = User::factory()->create();
    $server = YnhServer::factory()->for($serverOwner, 'user')->create([
        'secret' => 'tenant-agent-secret',
        'platform' => OsqueryPlatformEnum::LINUX,
    ]);
    $otherTenantUser = User::factory()->create();
    $ownPolicyUid = "cywise_{$serverOwner->tenant_id}_linux";
    $otherPolicyUid = "cywise_{$otherTenantUser->tenant_id}_linux";

    createOssecRuleForAgentTest(70001, $ownPolicyUid, 'Own Linux rules');
    createOssecRuleForAgentTest(70002, $otherPolicyUid, 'Other tenant Linux rules');

    $this->getJson(
        "/ossec-agent/{$server->secret}/policies/{$ownPolicyUid}/rules",
    )
        ->assertOk()
        ->assertJsonPath('rules.0.uid', 70001);

    $this->getJson(
        "/ossec-agent/{$server->secret}/policies/{$otherPolicyUid}/rules",
    )
        ->assertNotFound()
        ->assertJsonPath('message', 'Unknown OSSEC policy.');
});

test('the OSSEC agent endpoint rejects an unknown server secret', function () {
    createOssecRuleForAgentTest();

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
    createOssecRuleForAgentTest();

    $this->getJson("/ossec-agent/{$server->secret}/rules/50004")
        ->assertStatus(422);
});

test('the OSSEC policy endpoint rejects a policy incompatible with the server', function () {
    $user = User::factory()->create();
    $server = YnhServer::factory()->for($user, 'user')->create([
        'secret' => 'windows-agent-secret',
        'platform' => OsqueryPlatformEnum::WINDOWS,
    ]);
    createOssecRuleForAgentTest();

    $this->getJson(
        "/ossec-agent/{$server->secret}/policies/cywise_ossec_unix/rules",
    )
        ->assertStatus(422)
        ->assertJsonPath('message', 'The server is not compatible with this OSSEC policy.');
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
            'rule_title' => 'Check ownership and permissions for /etc/passwd',
            'status' => 'passed',
            'duration_ms' => '183',
            'error' => '[]',
            'text' => 'OSSEC rule Check ownership and permissions for /etc/passwd passed: the server is compliant.',
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
        ->and($stored->columns['rule_title'])->toBe('Check ownership and permissions for /etc/passwd')
        ->and($stored->message())->toBe('OSSEC rule Check ownership and permissions for /etc/passwd passed: the server is compliant.')
        ->and($stored->columns['status'])->toBe('passed');
});
