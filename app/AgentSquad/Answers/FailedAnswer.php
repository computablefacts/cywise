<?php

namespace App\AgentSquad\Answers;

class FailedAnswer extends AbstractAnswer
{
    public function __construct(string $answer, array $chainOfThought = [], string $nextAgent = null)
    {
        parent::__construct($answer, $chainOfThought, false, false, $nextAgent);
    }
}