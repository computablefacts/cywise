<?php

namespace App\AgentSquad\Answers;

use App\AgentSquad\ThoughtActionObservation;
use Parsedown;

abstract class AbstractAnswer
{
    private string $agent;
    private string $answer;
    /** @var ThoughtActionObservation[] $chainOfThought */
    private array $chainOfThought;
    private bool $success;
    private bool $final;

    protected function __construct(string $agent, string $answer, array $chainOfThought = [], bool $success = true, bool $final = false)
    {
        $this->agent = $agent;
        $this->answer = $answer;
        $this->chainOfThought = $chainOfThought;
        $this->success = $success;
        $this->final = $final;
    }

    public function __toString()
    {
        return ($this->success ? '[SUCCESS] ' : '[FAILURE] ') . $this->answer;
    }

    public function agent(): string
    {
        return $this->agent;
    }

    public function chainOfThought(): array
    {
        return $this->chainOfThought;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function failure(): bool
    {
        return !$this->success;
    }

    public function final(): bool
    {
        return $this->final;
    }

    public function html(): string
    {
        return (new Parsedown)->text($this->markdown());
    }

    public function markdown(): string
    {
        return $this->answer;
    }

    /** @param ThoughtActionObservation[] $chainOfThought */
    public function setChainOfThought(array $chainOfThought = []): void
    {
        $this->chainOfThought = $chainOfThought;
    }
}