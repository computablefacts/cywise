@extends('theme::iframes.app')

@section('content')
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
    <table class="table table-hover mb-0">
      <thead>
      <tr>
        <th>{{ __('Name') }}</th>
        <th>{{ __('Username') }}</th>
        <th>{{ __('Email') }}</th>
        <th>{{ __('Audit Report') }}</th>
      </tr>
      </thead>
      <tbody>
      @foreach($users as $user)
      <tr>
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

</script>
@endpush
