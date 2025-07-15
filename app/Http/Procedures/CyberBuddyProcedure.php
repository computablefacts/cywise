<?php

namespace App\Http\Procedures;

use App\AgentSquad\Actions\CyberBuddy;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Orchestrator;
use App\AgentSquad\Providers\LlmsProvider;
use App\Enums\RoleEnum;
use App\Jobs\ProcessIncomingEmails;
use App\Models\Conversation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class CyberBuddyProcedure extends Procedure
{
    public static string $name = 'cyberbuddy';

    #[RpcMethod(
        description: "Ask CyberBuddy to answer a question or execute some tasks.",
        params: [
            "thread_id" => "The thread identifier.",
            "directive" => "The user's directive.",
            "fallback_on_next_collection" => "Automatically search the next collection if the first one yields no result (optional)",
        ],
        result: [
            "response" => "CyberBuddy's introductory text.",
            "html" => "CyberBuddy's answer in HTML.",
        ]
    )]
    public function ask(Request $request): array
    {
        $params = $request->validate([
            'thread_id' => 'required|string|min:10|max:10|regex:/^[a-zA-Z0-9]+$/',
            'directive' => 'required|string|min:1|max:1024',
            'fallback_on_next_collection' => 'nullable|boolean',
        ]);
        $threadId = Str::trim($params['thread_id'] ?? '');
        $question = Str::trim($params['directive'] ?? '');
        $fallbackOnNextCollection = Str::lower($params['fallback_on_next_collection'] ?? 'true') === 'true';

        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('Unauthorized. Please log in and try again.');
        }

        /** @var Conversation $conversation */
        $conversation = Conversation::where('thread_id', $threadId)
            ->where('format', Conversation::FORMAT_V1)
            ->where('created_by', $user->id)
            ->first();

        if (!$conversation) {
            throw new \Exception("{$threadId} is an invalid thread id.");
        }
//        if (count($conversation->thread()) <= 0) {
//
//            $timestamp = Carbon::now();
//
//            // Load the prompt
//            /** @var Prompt $prompt */
//            $prompt = Prompt::where('created_by', $user->id)->where('name', 'default_assistant')->firstOrfail();
//            $prompt->template = Str::replace('{DATE}', $timestamp->format('Y-m-d'), $prompt->template);
//            $prompt->template = Str::replace('{TIME}', $timestamp->format('H:i'), $prompt->template);
//
//            // Set a conversation-wide prompt
//            $conversation->dom = json_encode(array_merge($conversation->thread(), [[
//                'role' => RoleEnum::DEVELOPER->value,
//                'content' => $prompt->template,
//                'timestamp' => Carbon::now()->toIso8601ZuluString(),
//            ]]));
//        }

        // Transform URLs provided by the user into notes
        ProcessIncomingEmails::extractAndSummarizeHyperlinks($question);

        // Load past messages
        $messages = $conversation->thread();

        // Dispatch work!
        try {
            $orchestrator = new Orchestrator();
            $orchestrator->registerAgent(new CyberBuddy());
            $answer = $orchestrator->run($user, $threadId, $messages, $question);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $answer = new FailedAnswer("Sorry, an error occurred: {$e->getMessage()}");
        }

        // Update the conversation
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => $question,
            'timestamp' => Carbon::now()->toIso8601ZuluString(),
        ];
        $messages[] = [
            'role' => RoleEnum::ASSISTANT->value,
            'content' => $answer->markdown(),
            'timestamp' => Carbon::now()->toIso8601ZuluString(),
            'chain_of_thought' => $answer->chainOfThought(),
            'html' => $answer->html(),
        ];
        $conversation->dom = json_encode($messages);
        $conversation->save();

        // Summarize the beginning of the conversation
        if (empty($conversation->description)) {
            $exchange = collect($conversation->lightThread())
                ->take(4)
                ->map(function (array $message) {
                    $msg = $message['content'] ?? '';
                    return Str::upper("> " . $message['role']) . " : {$msg}";
                })
                ->join("\n\n");
            $conversation->description = LlmsProvider::provide("Summarize the conversation in about 10 words :\n\n{$exchange}");
            $conversation->save();
        }
        return [
            'response' => [],
            'chain_of_thought' => $messages[count($messages) - 1]['chain_of_thought'] ?? '',
            'html' => $messages[count($messages) - 1]['html'] ?? '',
        ];
    }
}