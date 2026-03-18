<?php

namespace App\AgentSquad\Providers;

use App\Http\Procedures\NotesProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MemosProvider
{
    private User $user;
    private ?string $scope = null;

    public static function use(): MemosProvider
    {
        return new MemosProvider();
    }

    public function withUser(User $user): MemosProvider
    {
        $this->user = $user;
        return $this;
    }

    public function withScope(string $scope): MemosProvider
    {
        $this->scope = $scope;
        return $this;
    }

    public function provide(): string
    {
        try {
            $request = new JsonRpcRequest(['scope' => $this->scope]);
            $request->setUserResolver(fn() => $this->user);
            return (new NotesProcedure())->list($request)['notes']
                ->map(fn(array $note) => "## Memo {$note['creation_date']->format('Y-m-d H:i:s')}\n\n### {$note['subject']}\n\n{$note['body']}")
                ->join("\n\n");
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return '';
    }
}