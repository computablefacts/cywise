<?php

use App\Http\Controllers\Iframes\ScheduledTasksController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('scheduled-tasks');
render(function (Request $request) {
  return app(ScheduledTasksController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Scheduled Tasks')" :subtitle="__('Recurring and one-off tasks run on your behalf.')"/>

    <x-ui.card flush>
      @if($tasks->isEmpty())
        <x-ui.empty icon="clock">{{ __('None.') }}</x-ui.empty>
      @else
        <x-ui.table>
          <thead>
          <tr>
            <th class="ui:w-px">{{ __('Enabled') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Schedule') }}</th>
            <th>{{ __('Trigger') }}</th>
            <th>{{ __('Task') }}</th>
            <th>{{ __('Created By') }}</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          @foreach($tasks as $task)
            <tr>
              <td class="ui:text-center">
                <input type="checkbox" title="{{ __('Enabled') }}" onchange="pauseOrResumeTask({{ $task->id }})"
                       class="ui:size-4 ui:cursor-pointer ui:accent-brand-500" @checked($task->enabled)>
              </td>
              <td class="ui:font-medium">
                {{ $task->name }}
              </td>
              <td class="ui:text-slate-600">
                @if($task->run_once)
                  {{ __('once on') }} {{ $task->next_run_date->format('Y-m-d H:i:s') }}
                @else
                  {{ $task->readableCron() }}
                @endif
              </td>
              <td class="ui:text-slate-600">
                {{ empty($task->trigger) ? 'n/a' : $task->trigger }}
              </td>
              <td class="ui:text-slate-600">
                {{ $task->task }}
              </td>
              <td class="ui:text-slate-600">
                {{ $task->createdBy?->email }}
              </td>
              <td>
                <div class="ui:flex ui:justify-end">
                  <x-ui.icon-button icon="trash" :title="__('Delete')" class="ui:hover:text-red-600!"
                                    onclick="deleteTask({{ $task->id }})"/>
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </x-ui.table>
      @endif
    </x-ui.card>
  </div>
  <script>

    function deleteTask(taskId) {

      const response = confirm("{{ __('Are you sure you want to delete this task?') }}");

      if (response) {
        deleteScheduledTaskApiCall(taskId);
      }
    }

    function pauseOrResumeTask(taskId) {
      toggleScheduledTaskApiCall(taskId);
    }

  </script>
</x-layouts.app>