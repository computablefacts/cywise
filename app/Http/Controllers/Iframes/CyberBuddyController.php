<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CyberBuddyController extends Controller
{
    public function __invoke(Request $request): View
    {
        $params = $request->validate([
            'conversation_id' => ['nullable', 'integer', 'exists:cb_conversations,id'],
        ]);
        $conversationId = $params['conversation_id'] ?? null;

        /** @var User $user */
        $user = Auth::user();

        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                ->where('format', Conversation::FORMAT_V1)
                ->where('created_by', $user?->id)
                ->first();
        }

        /** @var Conversation $conversation */
        $conversation = $conversation ?? Conversation::create([
            'thread_id' => Str::random(10),
            'dom' => json_encode([]),
            'autosaved' => true,
            'created_by' => $user?->id,
            'format' => Conversation::FORMAT_V1,
        ]);

        return view('theme::iframes.cyberbuddy', ['threadId' => $conversation->thread_id]);
    }
}
