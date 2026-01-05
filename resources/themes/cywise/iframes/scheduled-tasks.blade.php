@extends('theme::iframes.app')

@section('content')
<div class="card mt-3 mb-3">
  @if($tasks->isEmpty())
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('None.') }}
      </div>
    </div>
  </div>
  @else
  <div class="card-body p-0">
    <table class="table mb-0">
      <thead>
      <tr>
        <th></th>
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
      <tr style="border-bottom-color:white">
        <td class="text-center">
          <input type="checkbox" title="{{ __('Enabled') }}" onchange="pauseOrResumeTask({{ $task->id }})" @checked($task->enabled)>
        </td>
        <td>
          {{ $task->name }}
        </td>
        <td class="text-center">
          {{ $task->readableCron() }}
        </td>
        <td>
          {{ $task->trigger }}
        </td>
        <td>
          {{ $task->task }}
        </td>
        <td>
          {{ $task->createdBy?->email }}
        </td>
        <td class="text-end">
          <a href="#" onclick="deleteTask({{ $task->id }})" class="text-decoration-none" style="color:red">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M4 7l16 0"/>
              <path d="M10 11l0 6"/>
              <path d="M14 11l0 6"/>
              <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
              <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
            </svg>
          </a>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>
@endsection

@push('scripts')
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
@endpush
