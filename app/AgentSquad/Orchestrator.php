<?php

namespace App\AgentSquad;

use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\MemosProvider;
use App\AgentSquad\Providers\PromptsProvider;
use App\Enums\RoleEnum;
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

    public function __construct(string $model = 'Qwen/Qwen3-Next-80B-A3B-Instruct')
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
        if ($depth >= 3) {

            Log::warning("Too many iterations: $depth");
            Log::warning("Messages: " . json_encode($messages));
            Log::warning("Chain-of-thought: " . json_encode($chainOfThought));

            /** @var ThoughtActionObservation $tao */
            $tao = array_pop($chainOfThought);
            $markdown = Str::trim(Str::replace('I_DONT_KNOW', '', $tao->observation()));

            if (empty($markdown)) {
                $markdown = __("I apologize, but I couldn't find any relevant references in my library.");
            }
            return new FailedAnswer($markdown, $chainOfThought);
        }
        if (!empty($messages)) {

            $lastMessage = $messages[count($messages) - 1];
            $nextAction = $lastMessage['next_action'] ?? null;

            if (isset($nextAction)) {

                $answer = $this->agents[$nextAction]->execute($user, $threadId, $messages, $input);

                /* if ($answer->failure()) {
                    $chainOfThought[] = new ThoughtActionObservation('Orchestrator bypass.', "{$nextAction}[{$input}]", 'An error occurred. Returning to the user.');
                    $chainOfThought = array_merge($answer->chainOfThought(), $chainOfThought);
                    $answer->setChainOfThought($chainOfThought);
                    return $answer;
                } */

                $chainOfThought[] = new ThoughtActionObservation('Orchestrator bypass.', "{$nextAction}[{$input}]", strip_tags($answer->markdown()));
                $chainOfThought = array_merge($answer->chainOfThought(), $chainOfThought);

                if ($answer->final()) {

                    $answer->setChainOfThought($chainOfThought);
                    $markdown = Str::trim(Str::replace('I_DONT_KNOW', '', $answer->markdown()));

                    if (empty($markdown)) {
                        return new FailedAnswer(__("I apologize, but I couldn't find any relevant references in my library."), $chainOfThought);
                    }
                    return $answer;
                }
            }
        }

        $template = '{"thought":"describe here succinctly your thoughts about the question you have been asked", "action_name":"set here the name of the action to execute", "action_input":"set here the input for the action"}';
        $cot = implode("\n", array_map(fn(ThoughtActionObservation $tao) => "> Thought: {$tao->thought()}\n> Observation: {$tao->observation()}", $chainOfThought));
        $actions = implode("\n", array_map(fn(AbstractAction $action) => "[ACTION][NAME]{$action->name()}[/NAME][DESCRIPTION]{$action->description()}[/DESCRIPTION][/ACTION]", array_filter($this->agents, fn(AbstractAction $action) => $action->isInvokable())));
        $prompt = PromptsProvider::provide('default_orchestrator', [
            'TEMPLATE' => $template,
            'COT' => $cot,
            'ACTIONS' => $actions,
            'INPUT' => $input,
            'MEMOS' => MemosProvider::provide($user),
        ]);
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => $prompt,
        ];
        $answer = LlmsProvider::provide($messages, $this->model);
        array_pop($messages);

        // Log::debug("[ORCHESTRATOR] Prompt: {$prompt}");
        // Log::debug("[ORCHESTRATOR] Answer: {$answer}");

        $matches = null;
        preg_match_all('/(?:```json\s*)?(.*)(?:\s*```)?/s', $answer, $matches);
        $answer = '{' . Str::after(Str::beforeLast(Str::trim($matches[1][0]), '}'), '{') . '}'; //  deal with "}<｜end▁of▁sentence｜>"
        $json = json_decode($answer, true);

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

        $answer = $this->agents[$json['action_name']]->execute($user, $threadId, $messages, $json['action_input']);

        if ($answer->failure()) {
            $chainOfThought[] = new ThoughtActionObservation($json['thought'], "{$json['action_name']}[{$json['action_input']}]", 'An error occurred. Returning to the user.');
            $chainOfThought = array_merge($answer->chainOfThought(), $chainOfThought);
            $answer->setChainOfThought($chainOfThought);
            return $answer;
        }

        $chainOfThought[] = new ThoughtActionObservation($json['thought'], "{$json['action_name']}[{$json['action_input']}]", strip_tags($answer->markdown()));
        $chainOfThought = array_merge($answer->chainOfThought(), $chainOfThought);

        if ($answer->final()) {

            $answer->setChainOfThought($chainOfThought);
            $markdown = Str::trim(Str::replace('I_DONT_KNOW', '', $answer->markdown()));

            if (empty($markdown)) {
                return new FailedAnswer(__("I apologize, but I couldn't find any relevant references in my library."), $chainOfThought);
            }
            return $answer;
        }
        return $this->processInput($user, $threadId, $messages, $input, $chainOfThought, $depth + 1);
    }
}