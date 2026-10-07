<?php

use App\Http\Controllers\Iframes\RulesEditorController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('rules-editor');
render(function (Request $request) {
  return app(RulesEditorController::class)($request);
});
?>

<x-layouts.app>
  @php
    // Alphabetical, as in the legacy form
    $enum = \App\Enums\OsqueryPlatformEnum::class;
    $platforms = [$enum::ALL, $enum::CENTOS, $enum::DARWIN, $enum::GENTOO, $enum::LINUX, $enum::POSIX, $enum::UBUNTU, $enum::WINDOWS];
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-5xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Edit rule')">
      <x-slot:actions>
        <x-ui.button variant="ghost" :href="route('rules')">{{ __('Cancel') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    {{-- Query language help --}}
    <div class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-blue-200 ui:bg-info-soft ui:p-4">
      <x-phosphor-info class="ui:size-5 ui:shrink-0 ui:text-info"/>
      <p class="ui:m-0 ui:text-sm ui:text-blue-900">
        {!! __('Rules are defined using the Osquery SQL-based query language. For detailed syntax, available tables, and examples, refer to the <a href="https://osquery.io/schema/" target="_blank">official Osquery schema documentation</a>.') !!}
      </p>
    </div>

    <x-ui.card>
      <div class="ui:flex ui:flex-col ui:gap-4">
        <x-ui.field :label="__('Name')" for="name">
          <x-ui.input id="name" value="{{ isset($rule->id) ? $rule->displayName() : '' }}" :disabled="isset($rule->id)"/>
        </x-ui.field>
        <x-ui.field :label="__('Description')" for="description">
          <x-ui.textarea id="description" rows="3">{{ $rule->comments }}</x-ui.textarea>
        </x-ui.field>
        <div class="ui:grid ui:gap-4 ui:sm:grid-cols-2 ui:lg:grid-cols-4">
          <x-ui.field :label="__('Category')" for="category">
            <x-ui.input id="category" value="{{ $rule->category }}"/>
          </x-ui.field>
          <x-ui.field :label="__('Platform')" for="platform">
            <x-ui.select id="platform">
              @foreach($platforms as $platform)
                <option value="{{ $platform->value }}" @selected($rule->platform === $platform)>{{ $platform->value }}</option>
              @endforeach
            </x-ui.select>
          </x-ui.field>
          <x-ui.field :label="__('Interval (in seconds)')" for="interval">
            <x-ui.input id="interval" value="{{ $rule->interval }}"/>
          </x-ui.field>
          <x-ui.field :label="__('Impact (between 0 and 100)')" for="score">
            <x-ui.input id="score" value="{{ $rule->score }}"/>
          </x-ui.field>
        </div>
        <label for="ioc" class="ui:m-0 ui:inline-flex ui:items-center ui:gap-2 ui:self-start ui:text-sm ui:text-ink ui:cursor-pointer">
          <input id="ioc" type="checkbox" role="switch" class="ui:m-0 ui:size-4 ui:accent-brand-500" @checked($rule->is_ioc)/>
          {{ __('Indicators of Compromise') }}
        </label>
        <div class="ui:overflow-hidden ui:rounded-lg">
          <div id="editor" style="height:200px;width:100%;"></div>
        </div>
        <div class="ui:flex ui:justify-end ui:gap-2">
          @if(isset($rule->id))
            <x-ui.button id="delete-rule" variant="secondary">
              <x-phosphor-trash class="ui:size-4 ui:text-red-600"/>
              {{ __('Delete') }}
            </x-ui.button>
          @endif
          <x-ui.button id="create-rule" icon="floppy-disk">{{ __('Save') }}</x-ui.button>
        </div>
      </div>
    </x-ui.card>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.6.0/ace.js"></script>
  <script>

    const editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/sql");
    editor.setValue(@json($rule->query ?? 'SELECT * FROM processes WHERE 1==0;'));

    const btnDelete = document.querySelector('#delete-rule');
    const btnCreate = document.querySelector('#create-rule');
    const elName = document.querySelector('#name');
    const elDescription = document.querySelector('#description');
    const elCategory = document.querySelector('#category');
    const elPlatform = document.querySelector('#platform');
    const elInterval = document.querySelector('#interval');
    const elScore = document.querySelector('#score');
    const elIoC = document.querySelector('#ioc');

    btnDelete?.addEventListener('click', () => {
      const response = confirm("{{ __('Are you sure you want to delete this rule?') }}");
      if (response) {
        deleteOsqueryRuleApiCall('{{ isset($rule->id) ? $rule->id : 0 }}');
      }
    });
    btnCreate.addEventListener('click', () => {
      createOsqueryRuleApiCall(elName.value, elDescription.value, elCategory.value, elPlatform.value, elInterval.value,
        elIoC.checked, elScore.value, editor.getValue(),
        () => window.toaster.toastSuccess("{{ __('The rule has been saved.') }}"));
    });

  </script>
</x-layouts.app>

