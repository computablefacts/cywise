<?php

use App\AgentSquad\ActionsRegistry;
use App\Models\RemoteAction;
use Sajya\Server\Testing\ProceduralRequests;

uses(ProceduralRequests::class);

describe('cyberbuddy@createRemoteAction', function () {

    test('admin can create a remote action (created_by is null)', function () {

        asTenant1User();
        becomeCywiseAdmin();

        $result = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 'admin_remote_action',
                'description' => 'Action created by admin',
                'url' => 'https://example.com/hook',
                'headers' => ['Authorization' => 'Bearer token'],
                'schema' => ['type' => 'object'],
                'payload_template' => ['hello' => 'world'],
                'response_template' => 'ok',
                'examples' => [],
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'action' => [
                        'id',
                        'created_by',
                        'name',
                        'description',
                        'url',
                        'headers',
                        'schema',
                        'payload_template',
                        'response_template',
                        'examples',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $action = RemoteAction::find($result->json('result.action.id'));

        expect($action)->not->toBeNull();
        expect($action->created_by)->toBeNull();
    });

    test('tenant user can create a remote action (created_by is user id)', function () {

        $user = tenant1User();
        asTenant1User();

        $result = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 'tenant_remote_action',
                'description' => 'Action created by tenant user',
                'url' => 'https://example.com/hook',
            ]);

        $actionId = $result->json('result.action.id');
        expect($actionId)->not->toBeNull();

        $action = RemoteAction::find($actionId);
        expect($action->created_by)->toBe($user->id);
    });
});

describe('cyberbuddy@deleteRemoteAction', function () {

    test('admin can delete admin-created and tenant-created remote actions', function () {

        // Create an admin action
        asTenant1User();
        becomeCywiseAdmin();

        $adminActionId = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 'admin_action_to_delete',
                'description' => 'To be deleted by admin',
                'url' => 'https://example.com/hook',
            ])
            ->json('result.action.id');

        expect(RemoteAction::find($adminActionId))->not->toBeNull();

        // Create a tenant action (same tenant)
        $tenantUser = tenant1User2();
        test()->actingAs($tenantUser);

        $tenantActionId = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 'tenant_action_to_delete',
                'description' => 'To be deleted by admin',
                'url' => 'https://example.com/hook',
            ])
            ->json('result.action.id');

        expect(RemoteAction::find($tenantActionId))->not->toBeNull();

        // Back to admin
        asTenant1User();
        becomeCywiseAdmin();

        // Admin deletes admin-created action
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@delete', [
                'action_id' => $adminActionId,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ]);

        expect(RemoteAction::find($adminActionId))->toBeNull();

        // Admin deletes tenant-created action
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@delete', [
                'action_id' => $tenantActionId,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ]);

        expect(RemoteAction::find($tenantActionId))->toBeNull();
    });

    test('tenant user can delete actions from own tenant but not admin-created or other-tenant actions', function () {

        // Prepare: admin-created action
        asTenant1User();
        becomeCywiseAdmin();

        $adminActionId = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 'admin_action_protected',
                'description' => 'Admin created',
                'url' => 'https://example.com/hook',
            ])
            ->json('result.action.id');

        expect(RemoteAction::find($adminActionId))->not->toBeNull();

        // Reset admin email so following users are not considered admins
        config()->set('towerify.admin.email', 'admin@cywise.local');

        // Tenant1 users create actions
        $u1 = tenant1User();
        test()->actingAs($u1);

        $tenant1Action1 = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 't1_action_one',
                'description' => 'T1 user1',
                'url' => 'https://example.com/hook',
            ])
            ->json('result.action.id');

        $u2 = tenant1User2();
        test()->actingAs($u2);

        $tenant1Action2 = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 't1_action_two',
                'description' => 'T1 user2',
                'url' => 'https://example.com/hook',
            ])
            ->json('result.action.id');

        // Tenant2 user creates an action
        $u3 = tenant2User();
        test()->actingAs($u3);

        $tenant2Action = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 't2_action',
                'description' => 'T2 user',
                'url' => 'https://example.com/hook',
            ])
            ->json('result.action.id');

        // Back to Tenant1 user (random user)
        $randomTenantUser = $u1;
        test()->actingAs($randomTenantUser);

        // Can delete own tenant actions (own and other user in same tenant)
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@delete', [
                'action_id' => $tenant1Action1,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ]);

        expect(RemoteAction::find($tenant1Action1))->toBeNull();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@delete', [
                'action_id' => $tenant1Action2,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ]);
        expect(RemoteAction::find($tenant1Action2))->toBeNull();

        // Cannot delete admin-created action (created_by = null)
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@delete', [
                'action_id' => $adminActionId,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ]);

        // Due to tenant scoping + whereIn on created_by, the admin action must still exist
        expect(RemoteAction::find($adminActionId))->not->toBeNull();

        // Cannot delete other tenant's action
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@delete', [
                'action_id' => $tenant2Action,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ]);

        // As current user (tenant1), other tenant's action is not visible anyway; verify it still exists by switching back to tenant2
        test()->actingAs($u3);
        expect(RemoteAction::find($tenant2Action))->not->toBeNull();
    });
});

describe('remoteactions@saveSettings + ActionsRegistry::enabledFor', function () {

    beforeEach(function () {
        // Ensure we start from a clean slate for RemoteAction and settings scoping will use the logged-in user
        // Create two users in tenant1 and one user in tenant2 using helpers
        $this->t1u1 = tenant1User();
        $this->t1u2 = tenant1User2();
        $this->t2u1 = tenant2User();
    });

    it('active au niveau tenant => tous les utilisateurs du tenant voient la remote action', function () {

        // Créer une RemoteAction en tant qu'utilisateur du tenant1
        test()->actingAs($this->t1u1);

        $createResp = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 't1_global_action',
                'description' => 'Action visible pour le tenant 1',
                'url' => 'https://example.com/hook',
            ]);

        $actionId = $createResp->json('result.action.id');
        expect($actionId)->not->toBeNull();

        // Activer l'action au niveau tenant (tenant1)
        $saveResp = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@saveSettings', [
                'scope_type' => 'tenant',
                'scope_id' => $this->t1u1->tenant_id,
                'actions' => ['t1_global_action'],
            ]);

        $saveResp->assertExactJsonStructure([
            'id', 'jsonrpc', 'result' => ['msg']
        ]);

        // Vérifier ActionsRegistry::enabledFor pour les deux utilisateurs du tenant1
        $enabledForU1 = collect(ActionsRegistry::enabledFor($this->t1u1))->map(fn($a) => $a->name())->all();
        $enabledForU2 = collect(ActionsRegistry::enabledFor($this->t1u2))->map(fn($a) => $a->name())->all();

        expect($enabledForU1)->toContain('t1_global_action');
        expect($enabledForU2)->toContain('t1_global_action');

        // Vérifier que l'utilisateur d'un autre tenant ne voit pas l'action (filtre HasTenant)
        test()->actingAs($this->t2u1);
        $enabledForOther = collect(ActionsRegistry::enabledFor($this->t2u1))->map(fn($a) => $a->name())->all();
        expect($enabledForOther)->not->toContain('t1_global_action');
    });

    it("active au niveau tenant mais désactivée pour un utilisateur => cet utilisateur ne l'a pas, les autres oui", function () {

        // Créer une action et l'activer au niveau tenant
        test()->actingAs($this->t1u1);

        $createResp = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 't1_partially_disabled_action',
                'description' => 'Action activée tenant mais off pour un user',
                'url' => 'https://example.com/hook',
            ]);

        expect($createResp->json('result.action.id'))->not->toBeNull();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@saveSettings', [
                'scope_type' => 'tenant',
                'scope_id' => $this->t1u1->tenant_id,
                'actions' => ['t1_partially_disabled_action'],
            ]);

        // Désactiver pour un utilisateur particulier (t1u2), conserver activée pour tenant => override utilisateur doit primer
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@saveSettings', [
                'scope_type' => 'user',
                'scope_id' => $this->t1u2->id,
                'actions' => [], // rien d'activé pour cet utilisateur
            ]);

        $enabledForU1 = collect(ActionsRegistry::enabledFor($this->t1u1))->map(fn($a) => $a->name())->all();
        $enabledForU2 = collect(ActionsRegistry::enabledFor($this->t1u2))->map(fn($a) => $a->name())->all();

        expect($enabledForU1)->toContain('t1_partially_disabled_action');
        expect($enabledForU2)->not->toContain('t1_partially_disabled_action');
    });

    it('désactivée au niveau tenant => aucun utilisateur du tenant ne voit la remote action', function () {

        // Créer une remote action pour tenant1
        test()->actingAs($this->t1u1);

        $createResp = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 't1_disabled_action',
                'description' => 'Action à désactiver pour le tenant 1',
                'url' => 'https://example.com/hook',
            ]);

        expect($createResp->json('result.action.id'))->not->toBeNull();

        // Sauvegarder les settings avec liste vide => toutes les autres actions seront à false pour ce tenant
        $saveResp = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@saveSettings', [
                'scope_type' => 'tenant',
                'scope_id' => $this->t1u1->tenant_id,
                'actions' => [],
            ]);

        $saveResp->assertExactJsonStructure(['id', 'jsonrpc', 'result' => ['msg']]);

        $enabledForU1 = collect(ActionsRegistry::enabledFor($this->t1u1))->map(fn($a) => $a->name())->all();
        $enabledForU2 = collect(ActionsRegistry::enabledFor($this->t1u2))->map(fn($a) => $a->name())->all();

        expect($enabledForU1)->not->toContain('t1_disabled_action');
        expect($enabledForU2)->not->toContain('t1_disabled_action');
    });

    it("désactivée au niveau tenant mais activée pour un utilisateur => seul cet utilisateur la voit", function () {

        // Créer une remote action pour tenant1
        test()->actingAs($this->t1u1);

        $createResp = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@create', [
                'name' => 't1_user_enabled_action',
                'description' => "Action désactivée pour le tenant mais activée pour un utilisateur",
                'url' => 'https://example.com/hook',
            ]);

        expect($createResp->json('result.action.id'))->not->toBeNull();

        // Désactiver au niveau tenant (liste vide)
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@saveSettings', [
                'scope_type' => 'tenant',
                'scope_id' => $this->t1u1->tenant_id,
                'actions' => [],
            ]);

        // Activer explicitement pour l'utilisateur t1u2 (override utilisateur)
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('remoteactions@saveSettings', [
                'scope_type' => 'user',
                'scope_id' => $this->t1u2->id,
                'actions' => ['t1_user_enabled_action'],
            ]);

        // Vérifier: t1u1 ne voit pas l'action, t1u2 la voit
        $enabledForU1 = collect(ActionsRegistry::enabledFor($this->t1u1))->map(fn($a) => $a->name())->all();
        $enabledForU2 = collect(ActionsRegistry::enabledFor($this->t1u2))->map(fn($a) => $a->name())->all();

        expect($enabledForU1)->not->toContain('t1_user_enabled_action');
        expect($enabledForU2)->toContain('t1_user_enabled_action');
    });

});
