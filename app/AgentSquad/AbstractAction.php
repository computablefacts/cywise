<?php

namespace App\AgentSquad;

use App\AgentSquad\Answers\AbstractAnswer;
use App\Models\User;

abstract class AbstractAction
{
    abstract static function schema(): array;

    public function name(): string
    {
        return $this->schema()['function']['name'] ?? '';
    }

    public function description(): string
    {
        return $this->schema()['function']['description'] ?? '';
    }

    public function isInvokable(): bool
    {
        return true;
    }

    public abstract function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer;
}