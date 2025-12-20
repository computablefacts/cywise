@extends('theme::iframes.app')

@php
$me = Auth::user();
@endphp

@push('styles')
<style>

  .pre-light {
    color: #565656;
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-wrap: break-word;
    background-color: #fff3cd;
    padding: 1rem;
  }

</style>
@endpush

@section('content')
<ul class="nav nav-tabs mt-3" role="tablist">
  <li class="nav-item" role="presentation">
    <a class="nav-link {{ isset($userSelected) ? '' : 'active' }}" id="simple-tab-0" data-bs-toggle="tab"
       href="#tab-tenant" role="tab">
      {{ __('Actions for all') }}
    </a>
  </li>
  <li class="nav-item" role="presentation">
    <a class="nav-link {{ isset($userSelected) ? 'active' : '' }}" id="simple-tab-1" data-bs-toggle="tab"
       href="#tab-user" role="tab">
      {{ __('User overrides') }}
    </a>
  </li>
</ul>
<div class="tab-content" id="tab-content">
  <div class="tab-pane {{ isset($userSelected) ? '' : 'active' }}" id="tab-tenant" role="tabpanel">
    <div class="card mt-3">
      <div class="card-body">
        <form id="form-tenant" method="post" action="#">
          @csrf
          <input type="hidden" name="scope_type" value="tenant">
          <input type="hidden" name="scope_id" value="{{ $me->tenant_id }}">
          @foreach($actions as $actionName => $instance)
          @php
          $checked = ($tenantSettings[$actionName]->enabled ?? true);
          @endphp
          <label class="mb-3">
            <input type="checkbox"
                   name="actions[]"
                   value="{{ $actionName }}"
                   @checked($checked)>
            <b>{{ $instance->name() }}</b> {{ $instance->isRemote() ? '(remote)' : '' }}
          </label>
          <pre class="pre-light mb-3">{{ \Str::trim($instance->description()) }}</pre>
          @endforeach
          <div class="mt-3 text-end">
            <button type="submit" class="btn btn-primary">
              {{ __('Save') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="tab-pane {{ isset($userSelected) ? 'active' : '' }}" id="tab-user" role="tabpanel">
    <div class="card mt-3 mb-3">
      <div class="card-body">
        <form method="get" action="{{ route('iframes.actions') }}" class="mb-3">
          <label class="mb-3">
            {{ __('Select a user') }}&nbsp;:&nbsp;
          </label>
          <select name="user_id" class="select select-bordered" onchange="this.form.submit()">
            <option value="">— {{ __('None') }} —</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" @selected(optional($userSelected)->id === $u->id)>
              {{ $u->name }} ({{ $u->email }})
            </option>
            @endforeach
          </select>
        </form>
        @if($userSelected)
        <form id="form-user" method="post" action="#" class="space-y-2">
          @csrf
          <input type="hidden" name="scope_type" value="user">
          <input type="hidden" name="scope_id" value="{{ $userSelected->id }}">
          @foreach($actions as $actionName => $instance)
          @php
          $checked = ($userSettings[$actionName]->enabled ?? null);
          @endphp
          <label class="mb-3">
            <input type="checkbox"
                   name="actions[]"
                   value="{{ $actionName }}"
                   @checked($checked=== null ? ($tenantSettings[$actionName]->enabled ?? true) : $checked)>
            <b>{{ $instance->name() }}</b> {{ $instance->isRemote() ? '(remote)' : '' }}
          </label>
          <pre class="pre-light mb-3">{{ \Str::trim($instance->description()) }}</pre>
          @endforeach
          <div class="mt-3 text-end">
            <button type="submit" class="btn btn-primary">
              {{ __('Save') }}
            </button>
          </div>
        </form>
        @else
        <p>
          {{ __('Select a user to override its defaults parameters.') }}
        </p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>

  function handleSubmit(form) {
    form.addEventListener('submit', (e) => {

      e.preventDefault();

      const scopeType = form.querySelector('input[name="scope_type"]').value;
      const scopeId = parseInt(form.querySelector('input[name="scope_id"]').value, 10);
      const actions = Array.from(form.querySelectorAll('input[name="actions[]"]:checked')).map(el => el.value);

      saveActionSettingsApiCall(scopeType, scopeId, actions);
    });
  }

  const elFormTenant = document.getElementById('form-tenant');

  if (elFormTenant) {
    handleSubmit(elFormTenant);
  }

  const elFormUser = document.getElementById('form-user');

  if (elFormUser) {
    handleSubmit(elFormUser);
  }

</script>
@endpush

