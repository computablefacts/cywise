<?php

use App\Models\YnhOsqueryRule;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

describe('osquery@create', function () {

    test('creates an osquery rule with minimal fields', function () {
        asTenant1User();
        becomeCywiseAdmin();

        $result = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@create', [
                'name' => 'minimal_rule',
                'category' => 'general',
                'description' => 'A minimal osquery rule.',
                'interval' => 1800,
                'is_ioc' => false,
                'score' => 0,
                'platform' => 'windows',
                'query' => 'SELECT * FROM system_info;',
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'rule' => [
                        'id',
                        'created_by',
                        'name',
                        'description',
                        'comments',
                        'category',
                        'platform',
                        'interval',
                        'is_ioc',
                        'score',
                        'query',
                        'enabled',
                        'snapshot',
                        'attck',
                        'version',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'name' => 'minimal_rule',
                'category' => 'general',
                'description' => 'A minimal osquery rule.',
                'interval' => 1800,
                'is_ioc' => false,
                'score' => 0,
                'platform' => 'windows',
                'query' => 'SELECT * FROM system_info;',
            ]);
        expect($result->json('result.rule.id'))->not->toBeNull();
    });

    test('changes rule name for non Cywise admin', function () {
        asTenant1User();

        $result = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@create', [
                'name' => 'standard_user_rule',
                'category' => 'general',
                'description' => 'A osquery rule created by a standard user.',
                'interval' => 3600,
                'is_ioc' => false,
                'score' => 0,
                'platform' => 'linux',
                'query' => 'SELECT * FROM system_info;',
            ]);
        expect($result->json('result.rule.name'))->toBe(tenant1User()->tenant_id . '_cywise_standard_user_rule');
    });

    test('fails to create osquery rule without required fields', function () {
        asTenant1User();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@create', [
                'name' => 'invalid_rule',
            ])
            ->assertJsonFragments([
                ['code' => -32602],
                ['category' => ['The category field is required.']],
                ['description' => ['The description field is required.']],
                ['interval' => ['The interval field is required.']],
                ['is_ioc' => ['The is ioc field is required.']],
                ['platform' => ['The platform field is required.']],
                ['query' => ['The query field is required.']],
                ['score' => ['The score field is required.']],
                ['message' => 'Invalid params'],
            ]);
    });

    test('creates ioc rule with a score', function () {
        asTenant1User();

        $result = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@create', [
                'name' => 'ioc_rule',
                'category' => 'ioc',
                'description' => 'An ioc osquery rule.',
                'interval' => 1800,
                'is_ioc' => true,
                'score' => 10,
                'platform' => 'windows',
                'query' => 'SELECT * FROM system_info;',
            ])
            ->assertJsonFragment(['score' => 10])
            ->assertJsonFragment(['is_ioc' => true]);
        expect($result->json('result.rule.id'))->not->toBeNull();
    });

    test('fails when an ioc rule has a 0 score', function () {
        asTenant1User();

        $result = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@create', [
                'name' => 'ioc_rule',
                'category' => 'ioc',
                'description' => 'An ioc osquery rule.',
                'interval' => 1800,
                'is_ioc' => true,
                'score' => 0,
                'platform' => 'windows',
                'query' => 'SELECT * FROM system_info;',
            ])
            ->assertJsonFragments([
                ['code' => 0],
                ['message' => 'The score must be greater than 0 but no greater than 100 if the rule is an indicator of compromise.'],
            ]);
    });

    test('fails when a not ioc rule has a score different from 0', function () {
        asTenant1User();

        $result = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@create', [
                'name' => 'ioc_rule',
                'category' => 'ioc',
                'description' => 'An ioc osquery rule.',
                'interval' => 1800,
                'is_ioc' => false,
                'score' => 10,
                'platform' => 'windows',
                'query' => 'SELECT * FROM system_info;',
            ])
            ->assertJsonFragments([
                ['code' => 0],
                ['message' => 'The score must be 0 if the rule is not an indicator of compromise.'],
            ]);
    });

    test('updates an existing rule', function () {
        asTenant1User();
        $rule = YnhOsqueryRule::factory()->create();

        $result = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@create', [
                'name' => $rule->displayName(),
                'category' => 'general',
                'description' => 'A osquery rule created by a standard user.',
                'interval' => 3600,
                'is_ioc' => false,
                'score' => 0,
                'platform' => 'linux',
                'query' => 'SELECT * FROM system_info;',
            ])
            ->assertJsonFragment([
                'category' => 'general',
                'description' => 'A osquery rule created by a standard user.',
                'interval' => 3600,
                'is_ioc' => false,
                'score' => 0,
                'platform' => 'linux',
                'query' => 'SELECT * FROM system_info;',
            ]);
        expect($result->json('result.rule.id'))->toBe($rule->id);
    });

});

describe('osquery@delete', function () {

    test('admin can delete a rule created by a tenant user', function () {

        // Create a rule as a regular user from tenant 1
        asTenant1User();

        $create = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@create', [
                'name' => 't1_rule_delete_me',
                'category' => 'general',
                'description' => 'Tenant1 user rule',
                'interval' => 1200,
                'is_ioc' => false,
                'score' => 0,
                'platform' => 'linux',
                'query' => 'SELECT 1;'
            ]);

        $ruleId = $create->json('result.rule.id');
        expect($ruleId)->not->toBeNull();

        // Act as another user, but mark as Cywise admin
        asTenant2User();
        becomeCywiseAdmin();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@delete', [
                'rule_id' => $ruleId,
            ])
            ->assertJsonFragment(['msg' => 'The rule has been removed!']);

        // Verify it is actually deleted
        expect(YnhOsqueryRule::find($ruleId))->toBeNull();
    });

    test('admin can create and then delete its own rule', function () {

        // Become admin and create a rule (created_by should be null)
        asTenant1User();
        becomeCywiseAdmin();

        $create = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@create', [
                'name' => 'admin_owned_rule',
                'category' => 'general',
                'description' => 'Admin rule',
                'interval' => 900,
                'is_ioc' => false,
                'score' => 0,
                'platform' => 'windows',
                'query' => 'SELECT * FROM system_info;'
            ]);

        $ruleId = $create->json('result.rule.id');

        expect($ruleId)->not->toBeNull();

        $rule = YnhOsqueryRule::find($ruleId);

        expect($rule)->not->toBeNull();
        expect($rule->created_by)->toBeNull();

        // Still admin, delete it
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('osquery@delete', [
                'rule_id' => $ruleId,
            ])
            ->assertJsonFragment(['msg' => 'The rule has been removed!']);

        expect(YnhOsqueryRule::find($ruleId))->toBeNull();
    });
});

describe('osquery@list', function () {
    // TODO
});

test('a user can update and delete rules from their tenant but not admin-created ones', function () {

    // 1) Create an admin rule
    asTenant1User();
    becomeCywiseAdmin();

    $adminCreate = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('osquery@create', [
            'name' => 'shared_rule',
            'category' => 'general',
            'description' => 'Admin created rule',
            'interval' => 600,
            'is_ioc' => false,
            'score' => 0,
            'platform' => 'linux',
            'query' => 'SELECT * FROM processes;'
        ]);

    $adminRuleId = $adminCreate->json('result.rule.id');
    expect($adminRuleId)->not->toBeNull();

    // 2) Demote admin and create a tenant rule by user1 (ensure non-admin)
    Config::set('towerify.admin.email', 'not-admin@cywise.local');

    asTenant1User();

    $tenantCreateByUser1 = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('osquery@create', [
            'name' => 'tenant_rule',
            'category' => 'general',
            'description' => 'Created by user1',
            'interval' => 700,
            'is_ioc' => false,
            'score' => 0,
            'platform' => 'linux',
            'query' => 'SELECT 2;'
        ]);

    $tenantRuleId = $tenantCreateByUser1->json('result.rule.id');
    expect($tenantRuleId)->not->toBeNull();

    // 3) User2 (same tenant) creates with the same name => upsert/update same rule
    asTenant1User2();

    $tenantUpdateByUser2 = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('osquery@create', [
            'name' => 'tenant_rule', // same base name; full name is tenant-scoped
            'category' => 'general',
            'description' => 'Updated by user2',
            'interval' => 701,
            'is_ioc' => false,
            'score' => 0,
            'platform' => 'linux',
            'query' => 'SELECT 3;'
        ])
        ->assertJsonFragment([
            'description' => 'Updated by user2',
            'interval' => 701,
        ]);

    // Upsert should keep the same id (same tenant-scoped name)
    expect($tenantUpdateByUser2->json('result.rule.id'))->toBe($tenantRuleId);

    // 4) User2 can delete the tenant rule
    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('osquery@delete', [
            'rule_id' => $tenantRuleId,
        ])
        ->assertJsonFragment(['msg' => 'The rule has been removed!']);

    expect(YnhOsqueryRule::find($tenantRuleId))->toBeNull();

    // 5) User2 cannot delete the admin-created rule
    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('osquery@delete', [
            'rule_id' => $adminRuleId,
        ])
        ->assertJsonFragment(['msg' => 'The rule has been removed!']);

    // Ensure the admin rule still exists (non-admin delete should have no effect)
    expect(YnhOsqueryRule::find($adminRuleId))->not->toBeNull();
});