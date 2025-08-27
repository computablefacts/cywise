<?php

namespace App\AgentSquad;

use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\Providers\LlmsProvider;
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

    public function __construct(string $model = 'meta-llama/Llama-4-Scout-17B-16E-Instruct')
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
            Log::error($e->getMessage());
            return new FailedAnswer("Sorry, an error occurred: {$e->getMessage()}");
        }
    }

    private function processCommand(User $user, string $threadId, array $messages, string $command): AbstractAnswer
    {
        if (!isset($this->commands[$command])) {
            return new FailedAnswer("Sorry, I did not find your command: {$command}");
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
            $observation = Str::trim(Str::replace('I_DONT_KNOW', '', $tao->observation()));

            if (empty($observation)) {
                $observation = __("I apologize, but I couldn't find any relevant references in my library.");
            }
            return new FailedAnswer($observation, $chainOfThought);
        }

        $template = '{"thought":"describe here succinctly your thoughts about the question you have been asked", "action_name":"set here the name of the action to execute", "action_input":"set here the input for the action"}';
        $cot = implode("\n", array_map(fn(ThoughtActionObservation $tao) => "> Thought: {$tao->thought()}\n> Observation: {$tao->observation()}", $chainOfThought));
        $actions = implode("\n", array_map(fn(AbstractAction $action) => "[ACTION][NAME]{$action->name()}[/NAME][DESCRIPTION]{$action->description()}[/DESCRIPTION][/ACTION]", $this->agents));
        $prompt = PromptsProvider::provide('default_orchestrator', [
            'TEMPLATE' => $template,
            'COT' => $cot,
            'ACTIONS' => $actions,
            'INPUT' => $input,
        ]);
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => $prompt,
        ];
        $answer = LlmsProvider::provide($messages, $this->model);
        array_pop($messages);

        Log::debug("[ORCHESTRATOR] Prompt: {$prompt}");
        Log::debug("[ORCHESTRATOR] Answer: {$answer}");

        $matches = null;
        preg_match_all('/(?:```json\s*)?(.*)(?:\s*```)?/s', $answer, $matches);
        $answer = Str::trim($matches[1][0]);
        $json = json_decode(Str::trim($answer), true);

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
            return new FailedAnswer("Invalid JSON response: {$answer}", $chainOfThought);
        }
        if (!isset($json['thought'])) {
            return new FailedAnswer("The thought is missing: {$answer}", $chainOfThought);
        }
        if (!isset($json['action_name'])) {
            return new FailedAnswer("The action name is missing: {$answer}", $chainOfThought);
        }
        if (!isset($json['action_input'])) {
            return new FailedAnswer("The action input is missing: {$answer}", $chainOfThought);
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
            return new FailedAnswer("The action is unknown: {$answer}", $chainOfThought);
        }

        $answer = $this->agents[$json['action_name']]->execute($user, $threadId, $messages, $json['action_input']);

        if ($answer->failure()) {
            $chainOfThought[] = new ThoughtActionObservation($json['thought'], "{$json['action_name']}[{$json['action_input']}]", 'An error occurred. Returning to the user.');
            $answer->setChainOfThought($chainOfThought);
            return $answer;
        }

        $chainOfThought[] = new ThoughtActionObservation($json['thought'], "{$json['action_name']}[{$json['action_input']}]", $answer->markdown());

        if ($answer->final()) {
            $answer->setChainOfThought($chainOfThought);
            return $answer;
        }
        return $this->processInput($user, $threadId, $messages, $input, $chainOfThought, $depth + 1);
    }
}