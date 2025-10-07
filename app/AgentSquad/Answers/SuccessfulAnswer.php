<?php

namespace App\AgentSquad\Answers;

class SuccessfulAnswer extends AbstractAnswer
{
    public function __construct(string $answer, array $chainOfThought = [], bool $final = false, ?string $nextAction = null)
    {
        parent::__construct($answer, $chainOfThought, true, $final, $nextAction);
    }
}