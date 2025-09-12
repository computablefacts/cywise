@extends('theme::iframes.app')

@section('content')
<div class="card mt-3">
  <form class="card-body">
    @if(isset($rule))
    <h6 class="card-title text-truncate">{{ __('Edit rule') }}</h6>
    <div class="card mb-3" style="background-color:#fff3cd;">
      <div class="card-body p-2">
        <div class="row">
          <div class="col">
            {!! __('Rules are defined using the Osquery SQL-based query language. For detailed syntax, available tables, and examples, refer to the <a href="https://osquery.io/schema/" target="_blank">official Osquery schema documentation</a>.') !!}
          </div>
        </div>
      </div>
    </div>
    <form>
      <div class="mb-3">
        <label for="name" class="form-label">{{ __('Name') }}</label>
        <input id="name" class="form-control" value="{{ $rule->name }}">
      </div>
      <div class="mb-3">
        <label for="description" class="form-label">{{ __('Description') }}</label>
        <textarea id="description" class="form-control" rows="3">{{ $rule->description }}</textarea>
      </div>
      <div class="mb-3">
        <label for="comment" class="form-label">{{ __('Comment') }}</label>
        <textarea id="comment" class="form-control" rows="3">{{ $rule->comment }}</textarea>
      </div>
      <div class="row mb-3">
        <div class="col">
          <label for="category" class="form-label">{{ __('Category') }}</label>
          <input id="category" class="form-control" value="{{ $rule->category }}">
        </div>
        <div class="col">
          <label for="platform" class="form-label">{{ __('Platform') }}</label>
          <select id="platform" class="form-select">
            <option value="{{ \App\Enums\OsqueryPlatformEnum::ALL->value }}"
                    {{ $rule->platform === \App\Enums\OsqueryPlatformEnum::ALL ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::ALL->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::CENTOS->value }}"
                    {{ $rule->platform === \App\Enums\OsqueryPlatformEnum::CENTOS ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::CENTOS->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::DARWIN->value }}"
                    {{ $rule->platform === \App\Enums\OsqueryPlatformEnum::DARWIN ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::DARWIN->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::GENTOO->value }}"
                    {{ $rule->platform === \App\Enums\OsqueryPlatformEnum::GENTOO ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::GENTOO->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::LINUX->value }}"
                    {{ $rule->platform === \App\Enums\OsqueryPlatformEnum::LINUX ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::LINUX->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::POSIX->value }}"
                    {{ $rule->platform === \App\Enums\OsqueryPlatformEnum::POSIX ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::POSIX->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::UBUNTU->value }}"
                    {{ $rule->platform === \App\Enums\OsqueryPlatformEnum::UBUNTU ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::UBUNTU->value }}
            </option>
            <option value="{{ \App\Enums\OsqueryPlatformEnum::WINDOWS->value }}"
                    {{ $rule->platform === \App\Enums\OsqueryPlatformEnum::WINDOWS ? 'selected' : '' }}>
              {{ \App\Enums\OsqueryPlatformEnum::WINDOWS->value }}
            </option>
          </select>
        </div>
        <div class="col">
          <label for="interval" class="form-label">{{ __('Interval (in seconds)') }}</label>
          <input id="interval" class="form-control" value="{{ $rule->interval }}">
        </div>
      </div>
      <div class="mb-3">
        <div class="form-check form-switch">
          <input id="ioc" class="form-check-input" type="checkbox" role="switch" {{ $rule->is_ioc ? 'checked' : '' }} />
          <label for="ioc" class="form-check-label">{{ __('Indicators of Compromise') }}</label>
        </div>
      </div>
      <div class="mb-3">
        <div id="editor" style="height:200px;width:100%;"></div>
      </div>
    </form>
    @else
    <h6 class="card-title text-truncate">{{ __('Create rule') }}</h6>
    <!-- TODO -->
    @endif
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.6.0/ace.js"></script>
<script>

  const editor = ace.edit("editor");
  editor.setTheme("ace/theme/monokai");
  editor.session.setMode("ace/mode/sql");
  editor.setValue('{{ $rule->query }}');

</script>
@endpush