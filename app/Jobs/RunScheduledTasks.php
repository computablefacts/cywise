<?php

namespace App\Jobs;

use App\AgentSquad\Providers\LlmsProvider;
use App\Http\Procedures\CyberBuddyProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Mail\MailCoachSimpleEmail;
use App\Models\Conversation;
use App\Models\ScheduledTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RunScheduledTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 3 * 180; // 9mn

    public function __construct()
    {
        //
    }

    public function handle()
    {
        ScheduledTask::where('next_run_date', '<=', Carbon::now())
            ->get()
            ->each(function (ScheduledTask $task) {
                try {

                    // Retrieve the user who created the task
                    /** @var User|null $user */
                    $user = User::find($task->created_by);

                    if (!$user) {
                        Log::warning("[RunScheduledTasks] Skipping task {$task->id} — user not found: {$task->created_by}");
                        return;
                    }

                    $user->actAs();
                    $threadId = Str::random(10);

                    /** @var Conversation $conversation */
                    $conversation = Conversation::where('thread_id', $threadId)
                        ->where('format', Conversation::FORMAT_V1)
                        ->where('created_by', $user->id)
                        ->first();

                    $conversation = $conversation ?? Conversation::create([
                        'thread_id' => $threadId,
                        'dom' => json_encode([]),
                        'autosaved' => true,
                        'created_by' => $user->id,
                        'format' => Conversation::FORMAT_V1,
                    ]);

                    // Step 1: Check condition (if provided)
                    $runTask = true;
                    $condition = Str::trim($task->condition);

                    if (!empty($condition)) {
                        $question = "Answer only with YES or NO and nothing else. Question: {$condition}";
                        $response = $this->ask($user, $threadId, $question);
                        $answer = $response['html'] ?? '';
                        $runTask = Str::contains(strip_tags($answer), ['oui', 'yes'], true);
                        Log::debug("[RunScheduledTasks] Condition evaluated for task {$task->id}: '{$condition}' => {$answer}");
                    }

                    // Step 2: Execute the task and email the result
                    $tsk = Str::trim($task->task);

                    if (!$runTask || empty($tsk)) {
                        Log::debug("[RunScheduledTasks] Skipping task {$task->id} because condition evaluated to false");
                    } else {
                        $response = $this->ask($user, $threadId, $tsk);
                        $answer = $response['html'] ?? '';
                        $summary = LlmsProvider::provide("Summarize this text in about 10 words :\n\n{$answer}");
                        MailCoachSimpleEmail::sendEmail("Cywise : {$summary}", "CyberBuddy vous répond !", $answer, $user->email);
                        Log::debug("[RunScheduledTasks] Emailed result for task {$task->id} to {$user->email}");
                    }

                    $task->prev_run_date = Carbon::now();
                    $task->next_run_date = Carbon::instance($task->cron()->getNextRunDate());
                    $task->save();

                } catch (\Exception $exception) {
                    Log::error($exception->getMessage());
                }
            });
    }

    private function ask(User $user, string $threadId, string $question): array
    {
        $request = new JsonRpcRequest([
            'thread_id' => $threadId,
            'directive' => $question,
        ]);
        $request->setUserResolver(fn() => $user);
        return (new CyberBuddyProcedure())->ask($request);
    }
}
