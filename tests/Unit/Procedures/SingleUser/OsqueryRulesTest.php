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
        expect($result->json('result.rule.name'))->toBe(tenant1User()->tenant_id.'_cywise_standard_user_rule');
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

describe('osquery@delete', function () {});

describe('osquery@list', function () {});
