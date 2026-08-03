<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\Answers\AbstractAnswer;
use App\Models\AppTrace;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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

    public function id(): ?int
    {
        return null;
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        $before = microtime(true);
        $answer = $this->execute2($user, $threadId, $messages, $input);
        $after = microtime(true);
        try {
            /** @var AppTrace $trace */
            $trace = AppTrace::create([
                'user_id' => $user->id,
                'verb' => 'GET',
                'endpoint' => "/agent-squad/action?name={$this->name()}",
                'duration_in_ms' => (int)(($after - $before) * 1000),
                'failed' => $answer->failure(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
        return $answer;
    }

    protected abstract function execute2(User $user, string $threadId, array $messages, string $input): AbstractAnswer;

    protected abstract function schema(): array;
}