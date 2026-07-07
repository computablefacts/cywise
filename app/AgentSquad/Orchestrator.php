<?php

namespace App\AgentSquad;

use App\AgentSquad\Actions\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\Assistants\TextAssistant;
use App\AgentSquad\Providers\MemosProvider;
use App\Enums\RoleEnum;
use App\Http\Procedures\NotesProcedure;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Orchestrator
{
    private string $model;
    /** @var AbstractAction[] $agents */
    private array $agents = [];
    /** @var AbstractAction[] $commands */
    private array $commands = [];

    public function __construct(string $model = 'deepseek-ai/DeepSeek-V4-Flash')
    {
        $this->model = $model;
    }

    public function registerAgent(AbstractAction $agent): void
    {
        $this->agents[$agent->name()] = $agent;
    }

    public function unregisterAgent(string $name): void
    {
        unset($this->agents[$name]);
    }

    public function registerCommand(AbstractAction $command): void
    {
        $this->commands[$command->name()] = $command;
    }

    public function unregisterCommand(string $name): void
    {
        unset($this->commands[$name]);
    }

    public function run(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        try {
            $input = Str::trim($input);
            if (Str::startsWith($input, '/')) {
                return $this->processCommand($user, $threadId, $messages, Str::after($input, '/'));
            }
            return $this->processInput($user, $threadId, $messages, $input);
        } catch (\Exception $e) {
            Log::error($e);
            return new FailedAnswer(__("Sorry, an error occurred: :msg", ['msg' => $e->getMessage()]));
        }
    }

    private function processCommand(User $user, string $threadId, array $messages, string $command): AbstractAnswer
    {
        if (!isset($this->commands[$command])) {
            return new FailedAnswer(__("Sorry, I did not find your command: :cmd", ['cmd' => $command]));
        }
        return $this->commands[$command]->execute($user, $threadId, $messages, $command);
    }

    private function processInput(User $user, string $threadId, array $messages, string $input, array $chainOfThought = [], int $depth = 0): AbstractAnswer
    {
        // Reduce the number of messages to avoid hitting the API limit on the number of tokens
        if (count($messages) > 10) {
            $messages = array_slice($messages, -10);
        }

        // Format history
        $history = '';

        foreach ($messages as $message) {
            $prefix = ($message['role'] ?? '') === RoleEnum::USER->value ? 'user > ' : 'assistant > ';
            $history .= $prefix . ($message['content'] ?? '') . "\n";
        }

        // Format chain-of-thought
        $cot = implode("\n", array_map(fn(ThoughtActionObservation $tao) => "> Thought: {$tao->thought()}\n> Action: {$tao->action()}\n> Observation: {$tao->observation()}", $chainOfThought));

        // If depth >= 15, we are stuck!
        if ($depth >= 15) {
            $answer = TextAssistant::use()
                ->withThreadId($threadId)
                ->withDeepInfraModel($this->model)
                ->withPrompt("default_orchestrator_stuck", [
                    'COT' => $cot,
                    'INPUT' => $input,
                    'HISTORY' => $history,
                    'DEPTH' => $depth,
                ])
                ->text();
            return new FailedAnswer($answer, $chainOfThought);
        }
        if (!empty($messages)) {

            $lastMessage = $messages[count($messages) - 1];
            $nextAction = $lastMessage['next_action'] ?? null;

            // Check if the next action to execute has been set by the last action executed
            if (isset($nextAction)) {

                $answer = $this->agents[$nextAction]->execute($user, $threadId, $messages, $input);
                $chainOfThought[] = new ThoughtActionObservation('Executing the next pre-defined action in sequence.', "{$nextAction}[{$input}]", $answer);
                $answer->setChainOfThought($chainOfThought);

                if ($answer->failure()) {
                    return $answer;
                }
                if ($answer->final()) {

                    $markdown = Str::trim(Str::replace('I_DONT_KNOW', '', $answer->markdown()));

                    if (empty($markdown)) {
                        return new FailedAnswer(__("I apologize, but I couldn't find any relevant references in my library."), $chainOfThought);
                    }
                    return $answer;
                }
            }
        }

        $template = '{"thought":"describe here succinctly your thoughts about the question you have been asked", "action_name":"set here the name of the action to execute", "action_input":"set here the input for the action"}';
        $actions = implode("\n", array_map(fn(AbstractAction $action) => "[ACTION][NAME]{$action->name()}[/NAME][DESCRIPTION]{$action->description()}[/DESCRIPTION][/ACTION]", array_filter($this->agents, fn(AbstractAction $action) => $action->isInvokable())));
        $result = TextAssistant::use()
            ->withThreadId($threadId)
            ->withDeepInfraModel($this->model)
            ->withPrompt('default_orchestrator', [
                'TEMPLATE' => $template,
                'COT' => $cot,
                'ACTIONS' => $actions,
                'INPUT' => $input,
                'HISTORY' => $history,
                'MEMOS' => MemosProvider::use()
                    ->withScope(NotesProcedure::SCOPE_IS_ORCHESTRATOR)
                    ->withUser($user)
                    ->provide(),
            ])
            ->structured();
        /** @var string $answer */
        $answer = $result->raw;
        /** @var array $json */
        $json = $result->parsed;

        if (!isset($json)) {

            $json = [];
            $matches = null;

            if (preg_match('/"thought"\s*:\s*"(.*?)"/is', $answer, $matches)) {
                $json['thought'] = $matches[1];
            }
            if (preg_match('/"action_name"\s*:\s*"([a-z0-9_]+)"/is', $answer, $matches)) {
                $json['action_name'] = $matches[1];
            }
            if (preg_match('/"action_input"\s*:\s*"(.*?)"/is', $answer, $matches)) {
                $json['action_input'] = $matches[1];
            }
        }
        if (empty($json)) {
            return new FailedAnswer(__("Invalid JSON response: :answer", ['answer' => $answer]), $chainOfThought);
        }
        if (!isset($json['thought'])) {
            return new FailedAnswer(__("The thought is missing: :answer", ['answer' => $answer]), $chainOfThought);
        }
        if (!isset($json['action_name'])) {
            return new FailedAnswer(__("The action name is missing: :answer", ['answer' => $answer]), $chainOfThought);
        }
        if (!isset($json['action_input'])) {
            return new FailedAnswer(__("The action input is missing: :answer", ['answer' => $answer]), $chainOfThought);
        }
        if ($json['action_name'] === 'respond_to_user') {
            $chainOfThought[] = new ThoughtActionObservation($json['thought'], "{$json['action_name']}[{$json['action_input']}]", 'Responding to the user.');
            return new SuccessfulAnswer($json['action_input'], $chainOfThought);
        }
        if ($json['action_name'] === 'clarify_request') {
            $chainOfThought[] = new ThoughtActionObservation($json['thought'], "{$json['action_name']}[{$json['action_input']}]", 'Asking for clarification.');
            return new SuccessfulAnswer($json['action_input'], $chainOfThought);
        }
        if (!isset($this->agents[$json['action_name']])) {
            $chainOfThought[] = new ThoughtActionObservation($json['thought'], "{$json['action_name']}[{$json['action_input']}]", 'An unknown action was requested. Returning to the user.');
            return new FailedAnswer(__("The action is unknown: :answer", ['answer' => $answer]), $chainOfThought);
        }
        if (is_array($json['action_input'])) { // edge case for remote actions : the input is a JSON string
            $json['action_input'] = json_encode($json['action_input']);
        }

        // Check for loop: if the same action with the same input was already executed in this chain
        foreach ($chainOfThought as $tao) {
            if ($tao->action() === "{$json['action_name']}[{$json['action_input']}]") {
                $answer = TextAssistant::use()
                    ->withThreadId($threadId)
                    ->withDeepInfraModel($this->model)
                    ->withPrompt("default_orchestrator_stuck", [
                        'COT' => $cot,
                        'INPUT' => $input,
                        'HISTORY' => $history,
                        'DEPTH' => $depth,
                    ])
                    ->text();
                return new FailedAnswer($answer, $chainOfThought);
            }
        }

        $answer = $this->agents[$json['action_name']]->execute($user, $threadId, $messages, $json['action_input']);
        $chainOfThought[] = new ThoughtActionObservation($json['thought'], "{$json['action_name']}[{$json['action_input']}]", $answer);
        $answer->setChainOfThought($chainOfThought);

        if ($answer->failure()) {
            return $answer;
        }
        if ($answer->final()) {

            $markdown = Str::trim(Str::replace('I_DONT_KNOW', '', $answer->markdown()));

            if (empty($markdown)) {
                return new FailedAnswer(__("I apologize, but I couldn't find any relevant references in my library."), $chainOfThought);
            }
            return $answer;
        }
        return $this->processInput($user, $threadId, $messages, $input, $chainOfThought, $depth + 1);
    }
}