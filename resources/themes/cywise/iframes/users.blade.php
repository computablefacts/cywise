@extends('theme::iframes.app')

@section('content')
<div class="px-3 pt-3" style="text-align: right;">
  <a href="{{ route('iframes.user-invitation') }}">
    {{ __('+ new') }}
  </a>
</div>
<div class="card mt-3 mb-3">
  @if($users->isEmpty())
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
        <th>{{ __('Name') }}</th>
        <th>{{ __('Username') }}</th>
        <th>{{ __('Email') }}</th>
        <th>{{ __('Audit Report') }}</th>
        <th>{{ __('Send audit report') }}</th>
      </tr>
      </thead>
      <tbody>
      @foreach($users as $user)
      <tr style="border-bottom-color:white">
        <td>
          <span class="font-lg mb-3 fw-bold">
            {{ isset($user->fullname) ? $user->fullname : $user->name }}
          </span>
        </td>
        <td>
          {{ isset($user->username) ? $user->username : '' }}
        </td>
        <td>
          <a href="mailto:{{ $user->email }}" target="_blank">
            {{ $user->email }}
          </a>
        </td>
        <td>
          <input type="checkbox" data-user-id="{{ $user->id }}" {{ $user->gets_audit_report ? 'checked' : '' }}>
        </td>
        <td>
          <button class="btn btn-sm btn-primary send-audit-report" data-user-id="{{ $user->id }}">
            {{ __('Send') }}
          </button>
        </td>
      </tr>
      <tr>
        <td colspan="5" class="pt-0">
          @foreach(collect($user->roles->all())->sortBy('name') as $role)
          <span class="lozenge information">{{ $role->name }}</span>
          @endforeach
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

  document.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
    checkbox.addEventListener('change',
      (event) => toggleGetsAuditReportApiCall(event.target.getAttribute('data-user-id'),
        response => toaster.toastSuccess(response.msg)));
  });

  document.querySelectorAll('.send-audit-report').forEach((button) => {
    button.addEventListener('click',
      (event) => {
        const userId = event.currentTarget.getAttribute('data-user-id');
        sendAuditReportApiCall(userId, response => toaster.toastSuccess(response.msg));
      });
  });

</script>
@endpush
