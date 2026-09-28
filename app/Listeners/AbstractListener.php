<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

abstract class AbstractListener implements ShouldQueue
{
    use InteractsWithQueue;

    const string CRITICAL = 'critical';
    const string MEDIUM = 'medium';
    const string LOW = 'low';

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 10 * 60; // 10 mn

    final public function handle($event)
    {
        // See https://stackoverflow.com/a/75492264
        pcntl_signal(SIGALRM, function () {
            Log::alert('Job killed!');
            exit;
        });
        try {
            $this->handle2($event);
        } catch (\Throwable $e) {
            Log::error($e);
        }
    }

    protected abstract function handle2($event);
}