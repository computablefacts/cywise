<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTask;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduledTasksController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tasks = ScheduledTask::query()
            ->orderByDesc('id')
            ->get();

        return view('theme::iframes.scheduled-tasks', [
            'tasks' => $tasks,
        ]);
    }
}
