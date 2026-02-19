<?php

namespace App\Http\Procedures;

use App\Http\Controllers\Iframes\TimelineController;
use App\Http\Requests\JsonRpcRequest;
use App\Jobs\ProcessIncomingEmails;
use App\Models\TimelineItem;
use App\Models\User;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class NotesProcedure extends Procedure
{
    public static string $name = 'notes';

    public const SCOPE_IS_ORCHESTRATOR = 'Orchestrator';
    public const SCOPE_IS_CYBERBUDDY = 'CyberBuddy';
    public const SCOPE_IS_SOC_OPERATOR = 'SOC Operator';

    #[RpcMethod(
        description: 'Create a note.',
        params: [
            'note' => 'The note content.',
            'scopes' => "An optional set of scopes associated with the note such as 'CyberBuddy', 'Orchestrator' or 'SOC Operator'",
        ],
        result: [
            'msg' => 'A success message.',
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'note' => 'required|string|min:1|max:1000',
            'scopes' => 'nullable|array|min:0|max:3',
            'scopes.*' => 'string|in:CyberBuddy,Orchestrator,SOC Operator',
        ]);

        /** @var User $user */
        $user = $request->user();
        $scopes = $params['scopes'] ?? [NotesProcedure::SCOPE_IS_CYBERBUDDY];
        $item = TimelineItem::createNote($user, $params['note'], '', $scopes);

        // Transform URLs provided by the user into notes
        ProcessIncomingEmails::extractAndSummarizeHyperlinks($params['note']);

        return [
            "msg" => "Your note has been saved!",
            "html" => TimelineController::noteAndMemo($user, $item)['html'] ?? '',
        ];
    }

    #[RpcMethod(
        description: "Delete a note.",
        params: [
            'note_id' => 'The note id.'
        ],
        result: [
            'msg' => 'A success message.',
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'note_id' => 'required|integer|exists:t_items,id',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var TimelineItem $item */
        $item = TimelineItem::fetchNotes($user->id, null, null, 0)
            ->filter(fn(TimelineItem $item) => $item->id == $params['note_id'])
            ->firstOrFail();
        $item->deleteItem();
        $item->save();

        return [
            "msg" => "Your note has been deleted!"
        ];
    }

    #[RpcMethod(
        description: 'List all notes.',
        params: [
            'scope' => "An optional scope such as 'CyberBuddy', 'Orchestrator' or 'SOC Operator'.",
        ],
        result: [
            'notes' => 'A list of notes.',
        ]
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'scope' => 'string|nullable|in:CyberBuddy,Orchestrator,SOC Operator',
        ]);

        /** @var User $user */
        $user = $request->user();
        $scope = $params['scope'] ?? null;

        return [
            'notes' => TimelineItem::fetchNotes($user->id, null, null, 0)
                ->filter(function (TimelineItem $note) use ($scope) {
                    if ($scope === null) {
                        return true;
                    }
                    $scopes = json_decode($note->attributes()['scopes'] ?? '[]');
                    return count($scopes) === 0 || in_array($scope, $scopes);
                })
                ->map(function (TimelineItem $note) {
                    $attributes = $note->attributes();
                    return [
                        'creation_date' => $note->timestamp,
                        'subject' => $attributes['subject'] ?? 'Unknown subject',
                        'body' => $attributes['body'] ?? '',
                        'scopes' => json_decode($attributes['scopes'] ?? '[]')
                    ];
                })
                ->values(),
        ];
    }
}
