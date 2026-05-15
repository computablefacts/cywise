<?php

namespace Tests\Feature\EventGraph;

use App\EventGraph\AttackGraph;
use App\Http\Procedures\EventsProcedure;
use Carbon\Carbon;
use Tests\TestCaseWithDb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryRule;

class AttackGraphTest extends TestCaseWithDb
{
    use RefreshDatabase;

    public function test_create_attack_graph_structure()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        $server = \App\Models\YnhServer::factory()->create(['created_by' => $user->id]);
        $procedure = new EventsProcedure();
        $date = Carbon::parse('2026-05-15');

        $graph = AttackGraph::create($user, $date, $server->id);
        $this->assertGreaterThan(0, $graph->nodes->count());
        $this->assertGreaterThan(0, $graph->edges->count());
        
        // Vérifier qu'une relation existe (ex: discovery -> credential_access)
        $discoveryNode = $graph->nodes->get('discovery');
        $this->assertNotNull($discoveryNode);
        $this->assertTrue(collect($discoveryNode->edges)->contains(fn($edge) => $edge->to->category === 'credential_access'));
    }

    public function test_find_paths()
    {
        // On a besoin d'événements pour que findPaths fonctionne maintenant
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        $server = \App\Models\YnhServer::factory()->create(['created_by' => $user->id]);
        $date = Carbon::parse('2026-05-15');

        $ruleDiscovery = YnhOsqueryRule::factory()->create(['category' => 'discovery']);
        $ruleCredential = YnhOsqueryRule::factory()->create(['category' => 'credential_access']);
        $ruleLateral = YnhOsqueryRule::factory()->create(['category' => 'lateral_movement']);

        $eventDiscovery = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleDiscovery->id,
            'calendar_time' => $date->copy()->setTime(10, 0),
        ]);
        $eventCredential = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleCredential->id,
            'calendar_time' => $date->copy()->setTime(11, 0),
        ]);
        $eventLateral = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleLateral->id,
            'calendar_time' => $date->copy()->setTime(12, 0),
        ]);

        $graph = AttackGraph::fromEvents(collect([$eventDiscovery, $eventCredential, $eventLateral]));
        
        // Chemin possible entre discovery et lateral_movement
        // discovery -> credential_access -> lateral_movement
        $paths = $graph->findPaths('discovery', 'lateral_movement');
        
        $this->assertNotEmpty($paths);
        foreach ($paths as $path) {
            $this->assertEquals('discovery', $path[0]);
            $this->assertEquals('lateral_movement', end($path));
        }
    }

    public function test_find_paths_with_time_constraint()
    {
        $server = \App\Models\YnhServer::factory()->create();
        $date = Carbon::parse('2026-05-15');

        $ruleDiscovery = YnhOsqueryRule::factory()->create(['category' => 'discovery']);
        $ruleCredential = YnhOsqueryRule::factory()->create(['category' => 'credential_access']);

        // Cas 1: Event A (10:00) <= Event B (11:00) -> Chemin doit exister
        $eventA1 = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleDiscovery->id,
            'calendar_time' => $date->copy()->setTime(10, 0),
        ]);
        $eventB1 = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleCredential->id,
            'calendar_time' => $date->copy()->setTime(11, 0),
        ]);

        $graph = AttackGraph::fromEvents(collect([$eventA1, $eventB1]));
        $paths = $graph->findPaths('discovery', 'credential_access');
        $this->assertNotEmpty($paths, 'Le chemin devrait exister car Event A <= Event B');

        // Cas 2: Event A (12:00) > Event B (11:00) -> Chemin ne doit PAS exister
        $eventA2 = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleDiscovery->id,
            'calendar_time' => $date->copy()->setTime(12, 0),
        ]);
        $eventB2 = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleCredential->id,
            'calendar_time' => $date->copy()->setTime(11, 0),
        ]);

        $graph2 = AttackGraph::fromEvents(collect([$eventA2, $eventB2]));
        $paths2 = $graph2->findPaths('discovery', 'credential_access');
        $this->assertEmpty($paths2, 'Le chemin ne devrait PAS exister car Event A > Event B');
    }

    public function test_node_completion_with_past_and_future_events()
    {
        // Créer un utilisateur et agir en tant que lui
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Créer un serveur appartenant à l'utilisateur
        $server = \App\Models\YnhServer::factory()->create(['created_by' => $user->id]);

        // Créer une règle
        $rule = YnhOsqueryRule::factory()->create([
            'name' => 'test_rule',
            'category' => 'discovery',
            'enabled' => true,
        ]);

        $targetDate = Carbon::parse('2026-05-15');

        // Événement passé (3 jours avant)
        YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $rule->id,
            'calendar_time' => $targetDate->copy()->subDays(3),
            'name' => 'past_event'
        ]);

        // Événement futur (3 jours après)
        YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $rule->id,
            'calendar_time' => $targetDate->copy()->addDays(3),
            'name' => 'future_event'
        ]);

        $graph = AttackGraph::create($user, $targetDate, $server->id);

        $node = $graph->nodes->get('discovery');
        $this->assertCount(2, $node->events);
        $this->assertTrue($node->events->contains(fn($e) => $e->name === 'past_event'));
        $this->assertTrue($node->events->contains(fn($e) => $e->name === 'future_event'));
    }

    public function test_format_paths()
    {
        $server = \App\Models\YnhServer::factory()->create();
        $date = Carbon::parse('2026-05-15');

        $ruleInitial = YnhOsqueryRule::factory()->create(['category' => 'initial_access']);
        $ruleCredential = YnhOsqueryRule::factory()->create(['category' => 'credential_access']);
        $ruleExecution = YnhOsqueryRule::factory()->create(['category' => 'execution']);

        // Scenario: initial_access (10:00) -> execution (10:30) -> credential_access (11:00)
        $eventInitial = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleInitial->id,
            'calendar_time' => $date->copy()->setTime(10, 0),
        ]);
        $eventExecution = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleExecution->id,
            'calendar_time' => $date->copy()->setTime(10, 30),
        ]);
        $eventCredential = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $ruleCredential->id,
            'calendar_time' => $date->copy()->setTime(11, 0),
        ]);

        $graph = AttackGraph::fromEvents(collect([$eventInitial, $eventExecution, $eventCredential]));
        $paths = $graph->findAllPaths();
        $formatted = $graph->tree($paths);

        // On s'attend à un seul arbre car tous les événements sont liés chronologiquement
        // └── 2026-05-15 10:00:00 [initial_access] ...
        //     └── 2026-05-15 10:30:00 [execution] ...
        //         └── 2026-05-15 11:00:00 [credential_access] ...

        $this->assertStringContainsString('10:00:00 [initial_access]', $formatted);
        $this->assertStringContainsString('10:30:00 [execution]', $formatted);
        $this->assertStringContainsString('11:00:00 [credential_access]', $formatted);
        
        // Vérifier la hiérarchie visuelle minimale (indentation)
        $lines = explode("\n", $formatted);
        $rootIndex = 0;
        if (str_contains($lines[0], '[Path Score:')) {
            $rootIndex = 1;
        }
        $this->assertStringContainsString('initial_access', $lines[$rootIndex]);
        $this->assertStringContainsString('    ', $lines[$rootIndex + 1]); // Indentation pour le premier fils
        $this->assertStringContainsString('execution', $lines[$rootIndex + 1]);
        $this->assertStringContainsString('    ', $lines[$rootIndex + 2]); // Indentation cumulée
        $this->assertStringContainsString('credential_access', $lines[$rootIndex + 2]);
    }
}
