<?php

namespace App\AgentSquad\Providers;

use App\Http\Procedures\NotesProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;

class MemosProvider extends AbstractProvider
{
    public static function provide(User $user, ?string $scope = null): string
    {
        $before = microtime(true);
        $request = new JsonRpcRequest(['scope' => $scope]);
        $request->setUserResolver(fn() => $user);
        $notes = (new NotesProcedure())->list($request)['notes']
            ->map(fn(array $note) => "## Memo {$note['creation_date']->format('Y-m-d H:i:s')}\n\n### {$note['subject']}\n\n{$note['body']}")
            ->join("\n\n");
        $after = microtime(true);

        self::traceSuccess('memos', $before, $after);
        return $notes;
    }
}