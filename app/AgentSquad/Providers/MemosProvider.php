<?php

namespace App\AgentSquad\Providers;

use App\Models\TimelineItem;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MemosProvider
{
    public static function provide(User $user, ?string $scope = null): string
    {
        $start = microtime(true);
        $notes = TimelineItem::fetchNotes($user->id, null, null, 0)
            ->filter(function (TimelineItem $note) use ($scope) {
                if ($scope === null) {
                    return true;
                }
                $scopes = json_decode($note->attributes()['scopes'] ?? '[]');
                return count($scopes) === 0 || in_array($scope, $scopes);
            })
            ->map(function (TimelineItem $note) {
                $attributes = $note->attributes();
                $subject = $attributes['subject'] ?? 'Unknown subject';
                $body = $attributes['body'] ?? '';
                return "## Memo {$note->timestamp->format('Y-m-d H:i:s')}\n\n### {$subject}\n\n{$body}";
            });
        $stop = microtime(true);
        Log::debug("[MEMOS_PROVIDER] Loading notes took " . ((int)ceil($stop - $start)) . " seconds and returned {$notes->count()} results");
        return $notes->join("\n\n");
    }
}