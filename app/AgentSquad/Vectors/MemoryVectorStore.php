<?php

namespace App\AgentSquad\Vectors;

class MemoryVectorStore extends AbstractVectorStore
{
    private array $vectors;

    public function __construct(int $topK = 4)
    {
        parent::__construct($topK);
        $this->vectors = [];
    }

    public function clear(): void
    {
        $this->vectors = [];
    }

    /** @param Vector[] $vectors */
    public function addVectors(array $vectors): void
    {
        $this->vectors = array_merge($this->vectors, $vectors);
    }

    protected function vectors(): \Generator
    {
        foreach ($this->vectors as $vector) {
            if ($vector->isValid()) {
                yield $vector;
            }
        }
    }
}
