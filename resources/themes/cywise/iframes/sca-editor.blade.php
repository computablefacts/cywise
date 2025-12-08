@extends('theme::iframes.app')

@section('content')
<div class="card mt-3 mb-3">
  <div class="card-body">
    <h6 class="card-title text-truncate">{{ __('Edit rule') }}</h6>
    <div class="form-group">
      <div class="row mb-3">
        <div class="col">
          <label for="name" class="form-label">{{ __('Name') }}</label>
          <input id="name" class="form-control"
                 value="{{ isset($check->id) ? $check->title : '' }}" {{ isset($check->id) ? 'disabled' : '' }}>
        </div>
        <div class="col-4">
          <label for="platform" class="form-label">{{ __('Platform') }}</label>
          <select id="platform" class="form-select">
            <!--
            <option value="{{ \App\Enums\OsqueryPlatformEnum::ALL->value }}"
                    {{ $check->policy?->platform() === \App\Enums\OsqueryPlatformEnum::ALL ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::ALL->value }}
            </option>
            -->
            <option value="{{ \App\Enums\OsqueryPlatformEnum::CENTOS->value }}"
                    {{ $check->policy?->platform() === \App\Enums\OsqueryPlatformEnum::CENTOS ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::CENTOS->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::DARWIN->value }}"
                    {{ $check->policy?->platform() === \App\Enums\OsqueryPlatformEnum::DARWIN ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::DARWIN->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::GENTOO->value }}"
                    {{ $check->policy?->platform() === \App\Enums\OsqueryPlatformEnum::GENTOO ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::GENTOO->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::LINUX->value }}"
                    {{ $check->policy?->platform() === \App\Enums\OsqueryPlatformEnum::LINUX ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::LINUX->value }}
            </option>
            <!--
            <option value="{{ \App\Enums\OsqueryPlatformEnum::POSIX->value }}"
                    {{ $check->policy?->platform() === \App\Enums\OsqueryPlatformEnum::POSIX ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::POSIX->value }}
            </option>
            -->
            <option value="{{ \App\Enums\OsqueryPlatformEnum::UBUNTU->value }}"
                    {{ $check->policy?->platform() === \App\Enums\OsqueryPlatformEnum::UBUNTU ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::UBUNTU->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::WINDOWS->value }}"
                    {{ $check->policy?->platform() === \App\Enums\OsqueryPlatformEnum::WINDOWS ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::WINDOWS->value }}
            </option>
          </select>
        </div>
      </div>
      <div class="mb-3">
        <label for="description" class="form-label">{{ __('Description') }}</label>
        <textarea id="description" class="form-control" rows="3">{{ $check->description }}</textarea>
      </div>
      <div class="mb-3">
        <label for="rationale" class="form-label">{{ __('Rationale') }}</label>
        <textarea id="rationale" class="form-control" rows="3">{{ $check->rationale }}</textarea>
      </div>
      <div class="mb-3">
        <label for="remediation" class="form-label">{{ __('Remediation') }}</label>
        <textarea id="remediation" class="form-control" rows="3">{{ $check->remediation }}</textarea>
      </div>
      <div class="mb-3">
        <div id="editor" style="height:200px;width:100%;"></div>
      </div>
      <div class="mb-3">
        <div class="col text-center">
          <button id="delete-rule" class="btn btn-danger {{ isset($check->id) ? '' : 'd-none' }}">
            {{ __('Delete') }}
          </button>
          <button id="create-rule" class="btn btn-primary">
            {{ __('Save') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.6.0/ace.js"></script>
<script>

  const editor = ace.edit("editor");
  editor.setTheme("ace/theme/monokai");
  editor.session.setMode("ace/mode/text");
  editor.setValue(@json($check->rule ?? ''));

  const btnDelete = document.querySelector('#delete-rule');
  const btnCreate = document.querySelector('#create-rule');
  const elName = document.querySelector('#name');
  const elDescription = document.querySelector('#description');
  const elRationale = document.querySelector('#rationale');
  const elRemediation = document.querySelector('#remediation');
  const elPlatform = document.querySelector('#platform');

  btnDelete.addEventListener('click', () => {
    const response = confirm("{{ __('Are you sure you want to delete this rule?') }}");
    if (response) {
      deleteOssecRuleApiCall('{{ isset($check->id) ? $check->id : 0 }}');
    }
  });
  btnCreate.addEventListener('click', () => {
    createOssecRuleApiCall(elName.value, elDescription.value, elRationale.value, elRemediation.value, elPlatform.value,
      editor.getValue(), () => toaster.toastSuccess("{{ __('The rule has been saved.') }}"));
  });

</script>
@endpush
