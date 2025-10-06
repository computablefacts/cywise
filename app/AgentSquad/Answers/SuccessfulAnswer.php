<?php

namespace App\AgentSquad\Answers;

class SuccessfulAnswer extends AbstractAnswer
{
    public function __construct(string $agent, string $answer, array $chainOfThought = [], bool $final = false)
    {
        parent::__construct($agent, $answer, $chainOfThought, true, $final);
    }
}