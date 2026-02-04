<?php

namespace App\Http\Procedures;

use App\AgentSquad\Actions\LabourLawyerConclusionsWriter;
use App\AgentSquad\ActionsRegistry;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Orchestrator;
use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Vectors\FileVectorStore;
use App\Enums\RoleEnum;
use App\Http\Requests\JsonRpcRequest;
use App\Jobs\ProcessIncomingEmails;
use App\Models\ActionSetting;
use App\Models\Conversation;
use App\Models\User;
use Carbon\Carbon;
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
            "html" => "CyberBuddy's answer in HTML.",
            "chain_of_thought" => "CyberBuddy's chain of thought.",
        ]
    )]
    public function ask(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'thread_id' => 'required|string|min:10|max:10|regex:/^[a-zA-Z0-9]+$/',
            'directive' => 'required|string|min:1|max:2048',
            'fallback_on_next_collection' => 'nullable|boolean',
        ]);
        $threadId = Str::trim($params['thread_id'] ?? '');
        $question = Str::trim($params['directive'] ?? '');
        $fallbackOnNextCollection = Str::lower($params['fallback_on_next_collection'] ?? 'true') === 'true';
        $user = $request->user();

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

        // Transform URLs provided by the user into notes
        ProcessIncomingEmails::extractAndSummarizeHyperlinks($question);

        // Load past messages
        $messages = $conversation->thread();

        // Dispatch work!
        try {
            $actions = ActionsRegistry::enabledFor($user);
            $orchestrator = new Orchestrator();

            if (!empty($actions)) {
                foreach ($actions as $name => $action) { // Register agents based on admin configuration
                    $orchestrator->registerAgent($action);
                }
            }
            if ($user->isCywiseAdmin()) { // TODO : move to the registry
                $output = FileVectorStore::unpack("labour_lawyer." . config('app.env') . ".zip.enc");
                $orchestrator->registerAgent(new LabourLawyerConclusionsWriter($output));
            }

            $answer = $orchestrator->run($user, $threadId, $messages, $question);

        } catch (\Exception $e) {
            $answer = new FailedAnswer(__("Sorry, an error occurred: :msg", ['msg' => $e->getMessage()]));
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
            'next_action' => $answer->nextAction(),
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
            'response' => [], // TODO : deprecated, remove ASAP
            'chain_of_thought' => $messages[count($messages) - 1]['chain_of_thought'] ?? '',
            'html' => $messages[count($messages) - 1]['html'] ?? '',
        ];
    }

    #[RpcMethod(
        description: "Delete an existing conversation.",
        params: [
            "conversation_id" => "The conversation identifier.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'conversation_id' => 'required|integer|exists:cb_conversations,id',
        ]);
        Conversation::where('id', $params['conversation_id'])->delete();
        return [
            'msg' => __('The conversation has been deleted!'),
        ];
    }

    #[RpcMethod(
        description: "Save action settings for tenant or user scope.",
        params: [
            "scope_type" => "Scope type: 'tenant' or 'user'.",
            "scope_id" => "The tenant id or the user id depending on scope_type.",
            "actions" => "Array of action names to enable (others will be disabled).",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function saveActionSettings(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'scope_type' => 'required|string|in:tenant,user',
            'scope_id' => 'required|integer|min:0',
            'actions' => 'array',
            'actions.*' => 'string',
        ]);
        $user = $request->user();
        $scopeType = $params['scope_type'];
        $scopeId = (int)$params['scope_id'];

        // Ensure scope is within current tenant
        if ($scopeType === 'tenant') {
            abort_if($scopeId !== $user->tenant_id, 403);
        } else {
            /** @var User $targetUser */
            $targetUser = User::findOrFail($scopeId);
            abort_unless($targetUser->tenant_id === $user->tenant_id, 403);
        }

        $enabledList = collect($params['actions'] ?? []);
        $actions = ActionsRegistry::all();

        foreach ($actions as $actionName => $action) {
            $enabled = $enabledList->contains($actionName);
            /** @var ActionSetting $setting */
            $setting = ActionSetting::firstOrNew([
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'action' => $actionName,
            ]);
            $setting->enabled = $enabled;
            $setting->save();
        }
        return [
            'msg' => __('Settings saved.'),
        ];
    }
}