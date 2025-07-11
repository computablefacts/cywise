<?php

namespace App\Console\Commands;

use App\AgentSquad\Actions\CyberBuddy;
use App\AgentSquad\Orchestrator;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AnswerCyberQuestion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cyberbuddy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interact with CyberBuddy from the command line.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $input = $this->ask('Quelle est votre question ?');
        $messages = [];
        $orchestrator = new Orchestrator();
        $orchestrator->registerAgent(new CyberBuddy());
        $user = User::query()->where('email', config('towerify.admin.email'))->first();

        Auth::login($user);

        while (true) {
            $answer = $orchestrator->run($user, "123abc", $messages, $input);
            $messages[] = [
                "role" => RoleEnum::USER->value,
                "content" => $input,
            ];
            $messages[] = [
                "role" => RoleEnum::ASSISTANT->value,
                "content" => $answer->markdown(),
            ];
            Log::debug($messages);
            $input = $this->ask($answer->markdown());
        }
    }
}
