<?php

namespace App\AgentSquad;

use App\AgentSquad\Answers\AbstractAnswer;
use App\Models\User;

abstract class AbstractAction
{
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

    public function isRemote(): bool
    {
        return false;
    }

    public abstract function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer;

    protected abstract function schema(): array;
}