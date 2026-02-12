<?php

namespace App\AgentSquad\Providers;

use App\Models\TimelineItem;
use App\Models\User;

class MemosProvider extends AbstractProvider
{
    public static function provide(User $user, ?string $scope = null): string
    {
        $before = microtime(true);

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
            })
            ->join("\n\n");

        $after = microtime(true);

        self::traceSuccess('memos', $before, $after);
        return $notes;
    }
}