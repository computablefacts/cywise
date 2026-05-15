<?php

namespace App\EventGraph;

use App\Models\YnhOsquery;
use Illuminate\Support\Collection;

class Node
{
    public string $category;
    public string $name;
    public string $description;
    public Collection $events;
    public array $edges = [];

    public function __construct(string $category, array $details)
    {
        $this->category = $category;
        $this->name = $details['name'] ?? $category;
        $this->description = $details['description'] ?? '';
        $this->events = collect();
    }

    public function addEvent(YnhOsquery $event): void
    {
        $this->events->push($event);
    }

    public function addEdge(Edge $edge): void
    {
        $this->edges[] = $edge;
    }

    public function isEmpty(): bool
    {
        return $this->events->isEmpty();
    }
}
