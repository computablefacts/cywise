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

    #[RpcMethod(
        description: "Add a note to the timeline.",
        params: [
            'note' => 'The note content.'
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'note' => 'required|string|min:1|max:1000',
        ]);

        /** @var User $user */
        $user = $request->user();
        $item = TimelineItem::createNote($user, $params['note']);

        // Transform URLs provided by the user into notes
        ProcessIncomingEmails::extractAndSummarizeHyperlinks($params['note']);

        return [
            "msg" => "Your note has been saved!",
            "html" => TimelineController::noteAndMemo($user, $item)['html'] ?? '',
        ];
    }

    #[RpcMethod(
        description: "Delete a single note from the timeline.",
        params: [
            'note_id' => 'The note id.'
        ],
        result: [
            "msg" => "A success message.",
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
}
