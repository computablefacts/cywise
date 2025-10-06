<?php

namespace App\AgentSquad\Answers;

class FailedAnswer extends AbstractAnswer
{
    public function __construct(string $agent, string $answer, array $chainOfThought = [])
    {
        parent::__construct($agent, $answer, $chainOfThought, false);
    }
}