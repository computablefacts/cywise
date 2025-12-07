<?php

namespace App\Jobs;

use App\Models\ScheduledTask;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

                    // TODO

                    $task->prev_run_date = Carbon::now();
                    $task->next_run_date = Carbon::instance($task->cron()->getNextRunDate());
                    $task->save();

                } catch (\Exception $exception) {
                    Log::error($exception->getMessage());
                }
            });
    }
}
