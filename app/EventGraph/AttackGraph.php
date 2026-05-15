<?php

namespace App\EventGraph;

use App\Http\Procedures\EventsProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use App\Models\YnhOsquery;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttackGraph
{
    /** @var Collection<string, Node> */
    public Collection $nodes;

    /** @var Collection<Edge> */
    public Collection $edges;

    public function __construct()
    {
        $this->nodes = collect();
        $this->edges = collect();
    }

    public static function create(User $user, Carbon $date, ?int $serverId = null): self
    {
        $procedure = new EventsProcedure();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // 1. Récupérer les événements du jour
        $request = new JsonRpcRequest();
        $request->replace([
            'min_score' => 0,
            'server_id' => $serverId,
            'window' => [
                $date->format('Y-m-d'),
                $date->format('Y-m-d'),
            ],
            'categories' => array_keys(self::eventCategories()),
        ]);
        $request->setUserResolver(fn() => $user);
        $events = $procedure->list($request)['events'];
        $graph = self::fromEvents($events);

        // 2. Compléter les noeuds vides avec des événements passés et futurs
        foreach ($graph->nodes as $category => $node) {
            if ($node->isEmpty()) {

                // Chercher l'événement le plus proche dans le passé
                $request = new JsonRpcRequest();
                $request->replace([
                    'min_score' => 0,
                    'server_id' => $serverId,
                    'window' => [
                        $date->copy()->subYear()->format('Y-m-d'),
                        $date->format('Y-m-d'),
                    ],
                    'categories' => [$category],
                ]);
                $request->setUserResolver(fn() => $user);
                $events = $procedure->list($request)['events'];
                $event = $events->filter(fn(YnhOsquery $e) => $e->calendar_time->lt($startOfDay))->first();

                if ($event) {
                    $node->addEvent($event);
                }

                // Chercher l'événement le plus proche dans le futur
                $request = new JsonRpcRequest();
                $request->replace([
                    'min_score' => 0,
                    'server_id' => $serverId,
                    'window' => [
                        $date->format('Y-m-d'),
                        $date->copy()->addYear()->format('Y-m-d'),
                    ],
                    'categories' => [$category],
                ]);
                $request->setUserResolver(fn() => $user);
                $events = $procedure->list($request)['events'];
                $event = $events->filter(fn(YnhOsquery $e) => $e->calendar_time->gt($endOfDay))->reverse()->first();

                if ($event) {
                    $node->addEvent($event);
                }
            }
        }
        return $graph;
    }

    public static function fromEvents(Collection $events): self
    {
        $graph = new self();
        $categories = self::eventCategories();
        $relationships = self::relationshipsBetweenCategories();

        foreach ($categories as $category => $details) {
            $graph->addNode($category, $details);
        }
        foreach ($relationships as $from => $tos) {
            foreach ($tos as $to) {
                $graph->addEdge($from, $to);
            }
        }
        /** @var YnhOsquery $event */
        foreach ($events as $event) {
            $category = $event->rule_category ?? ($event->rule->category ?? null);
            if ($category && $node = $graph->nodes->get($category)) {
                $node->addEvent($event);
            }
        }
        return $graph;
    }

    private static function eventCategories(): array
    {
        return [
            'discovery' => [
                'name' => 'Reconnaissance',
                'description' => 'Scan et découverte du réseau/des cibles.',
                'attck_phases' => ['TA0043'], // ATT&CK Phase: Reconnaissance
            ],
            'initial_access' => [
                'name' => 'Accès Initial',
                'description' => 'Obtention du premier accès à la cible.',
                'attck_phases' => ['TA0001'], // ATT&CK Phase: Initial Access
            ],
            'persistence' => [
                'name' => 'Persistance',
                'description' => 'Mécanismes pour maintenir l\'accès.',
                'attck_phases' => ['TA0003'], // ATT&CK Phase: Persistence
            ],
            'execution' => [
                'name' => 'Exécution',
                'description' => 'Exécution de code malveillant.',
                'attck_phases' => ['TA0002'], // ATT&CK Phase: Execution
            ],
            'exfiltration' => [
                'name' => 'Exfiltration',
                'description' => 'Vol de données.',
                'attck_phases' => ['TA0010'], // ATT&CK Phase: Exfiltration
            ],
            'tunneling' => [
                'name' => 'Tunneling',
                'description' => 'Création de tunnels pour masquer le trafic.',
                'attck_phases' => ['TA0008'], // ATT&CK Phase: Lateral Movement (approximation)
            ],
            'credential_access' => [
                'name' => 'Accès aux Identifiants',
                'description' => 'Vol de mots de passe ou de clés d\'accès.',
                'attck_phases' => ['TA0006'], // ATT&CK Phase: Credential Access
            ],
            'lateral_movement' => [
                'name' => 'Déplacement Latéral',
                'description' => 'Déplacement dans le réseau après compromission initiale.',
                'attck_phases' => ['TA0008'], // ATT&CK Phase: Lateral Movement
            ],
            'defense_evasion' => [
                'name' => 'Évasion de Défense',
                'description' => 'Techniques pour éviter la détection.',
                'attck_phases' => ['TA0005'], // ATT&CK Phase: Defense Evasion
            ],
            'privilege_escalation' => [
                'name' => 'Élévation de Privilèges',
                'description' => 'Obtention de droits administrateur.',
                'attck_phases' => ['TA0004'], // ATT&CK Phase: Privilege Escalation
            ],
        ];
    }

    private static function relationshipsBetweenCategories(): array
    {
        return [
            'discovery' => ['credential_access', 'initial_access'],
            'initial_access' => ['persistence', 'execution', 'credential_access', 'privilege_escalation'],
            'persistence' => ['execution', 'exfiltration', 'lateral_movement'],
            'execution' => ['exfiltration', 'tunneling', 'credential_access', 'defense_evasion'],
            'exfiltration' => ['tunneling'],
            'tunneling' => ['persistence', 'lateral_movement'],
            'credential_access' => ['lateral_movement', 'privilege_escalation'],
            'lateral_movement' => ['execution', 'exfiltration'],
            'defense_evasion' => ['execution', 'persistence'],
            'privilege_escalation' => ['execution', 'persistence'],
        ];
    }

    public function addNode(string $category, array $details): Node
    {
        $node = new Node($category, $details);
        $this->nodes->put($category, $node);
        return $node;
    }

    public function addEdge(string $fromCategory, string $toCategory): void
    {
        $fromNode = $this->nodes->get($fromCategory);
        $toNode = $this->nodes->get($toCategory);

        if ($fromNode && $toNode) {
            $edge = new Edge($fromNode, $toNode);
            $this->edges->push($edge);
            $fromNode->addEdge($edge);
        }
    }

    public function findAllPaths(): array
    {
        $allScenarios = [];
        $allEvents = $this->nodes->flatMap(fn(Node $node) => $node->events);

        // 1. Générer tous les scénarios d'événements possibles
        foreach ($allEvents as $event) {
            $this->collectAllPaths($event, [$event], $allScenarios);
        }

        // 2. Filtrer pour ne garder que les scénarios uniques les plus longs
        $maximalScenarios = [];

        // Trier par longueur décroissante pour faciliter le filtrage
        usort($allScenarios, fn($a, $b) => count($b) <=> count($a));

        foreach ($allScenarios as $scenario) {

            $ids = array_map(fn($e) => $e->id, $scenario);
            $idsString = implode(',', $ids);
            $isSubScenario = false;

            foreach ($maximalScenarios as $maximalScenario) {

                $maximalIds = array_map(fn($e) => $e->id, $maximalScenario);
                $maximalIdsString = implode(',', $maximalIds);

                if (str_contains($maximalIdsString, $idsString)) {
                    $isSubScenario = true;
                    break;
                }
            }
            if (!$isSubScenario) {
                $maximalScenarios[] = $scenario;
            }
        }
        return $maximalScenarios;
    }

    private function collectAllPaths(YnhOsquery $currentEvent, array $currentScenario, array &$allScenarios): void
    {
        $allScenarios[] = $currentScenario;
        $node = $this->nodes->get($currentEvent->category());

        if (!$node) {
            return;
        }

        $visitedEventIds = array_map(fn($e) => $e->id, $currentScenario);

        foreach ($node->edges as $edge) {

            $nextEvents = $edge->to->events->filter(fn(YnhOsquery $nextEvent) => $nextEvent->calendar_time->gte($currentEvent->calendar_time) && !in_array($nextEvent->id, $visitedEventIds));

            foreach ($nextEvents as $nextEvent) {
                $this->collectAllPaths($nextEvent, array_merge($currentScenario, [$nextEvent]), $allScenarios);
            }
        }
    }

    public function findPaths(string $startCategory, string $endCategory): array
    {
        if (!$this->nodes->has($startCategory) || !$this->nodes->has($endCategory)) {
            return [];
        }

        $paths = [];
        $this->dfs($startCategory, $endCategory, [], $paths);

        return $paths;
    }

    public function tree(array $paths): string
    {
        $allEvents = $this->nodes->flatMap(fn(Node $node) => $node->events)->sortBy('calendar_time');

        // Un événement est une "racine" s'il n'apparaît comme descendant d'aucun autre événement dans le graphe
        $roots = $allEvents->filter(function (YnhOsquery $event) use ($allEvents) {
            foreach ($allEvents as $otherEvent) {
                if ($otherEvent->id === $event->id) {
                    continue;
                }
                $node = $this->nodes->get($otherEvent->category());
                if (!$node) {
                    continue;
                }
                foreach ($node->edges as $edge) {
                    if ($edge->to->category === $event->category()) {
                        if ($event->calendar_time->gte($otherEvent->calendar_time)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        });

        $output = "";
        $count = $roots->count();
        $index = 0;

        foreach ($roots as $root) {
            $isLast = (++$index === $count);
            $output .= $this->renderTree($root, "", $isLast);
        }
        return trim($output);
    }

    private function renderTree(YnhOsquery $event, string $prefix, bool $isLast): string
    {
        $branch = $isLast ? "└── " : "├── ";
        $output = $prefix . $branch . $event->calendar_time->format('Y-m-d H:i:s') . " [{$event->category()}] " . $event->message() . "\n";
        $node = $this->nodes->get($event->category());
        $children = collect();

        if ($node) {
            foreach ($node->edges as $edge) {
                $nextEvents = $edge->to->events->filter(fn($e) => $e->calendar_time->gte($event->calendar_time));
                foreach ($nextEvents as $nextEvent) {
                    $children->push($nextEvent);
                }
            }
        }

        // Éviter les doublons de descendants et trier par temps
        $children = $children->unique('id')->sortBy('calendar_time');
        $newPrefix = $prefix . ($isLast ? "    " : "│   ");
        $childCount = $children->count();
        $childIndex = 0;

        foreach ($children as $child) {
            $isLastChild = (++$childIndex === $childCount);
            $output .= $this->renderTree($child, $newPrefix, $isLastChild);
        }
        return $output;
    }

    private function dfs(string $current, string $target, array $visited, array &$paths): void
    {
        $visited[] = $current;

        if ($current === $target) {
            $paths[] = $visited;
            return;
        }

        $node = $this->nodes->get($current);

        foreach ($node->edges as $edge) {
            if (!in_array($edge->to->category, $visited)) {

                // Un chemin A->B est possible si il existe un event du noeud A dont la calendar_time est <= un event du noeud B
                if ($this->hasTimeValidRelation($node, $edge->to)) {
                    $this->dfs($edge->to->category, $target, $visited, $paths);
                }
            }
        }
    }

    private function hasTimeValidRelation(Node $fromNode, Node $toNode): bool
    {
        if ($fromNode->events->isEmpty() || $toNode->events->isEmpty()) {
            return false;
        }

        // On cherche s'il existe au moins une paire (eventA, eventB) telle que eventA.time <= eventB.time
        $minTimeA = $fromNode->events->min('calendar_time');
        $maxTimeB = $toNode->events->max('calendar_time');

        return $minTimeA <= $maxTimeB;
    }
}
