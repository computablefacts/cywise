<?php

use App\Http\Controllers\Iframes\ScaEditorController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('sca-editor');
render(function (Request $request) {
  return app(ScaEditorController::class)($request);
});
?>

<x-layouts.app>
  @php
    // Alphabetical, as in the legacy form (ALL and POSIX are not offered)
    $enum = \App\Enums\OsqueryPlatformEnum::class;
    $platforms = [$enum::CENTOS, $enum::DARWIN, $enum::GENTOO, $enum::LINUX, $enum::UBUNTU, $enum::WINDOWS];
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-5xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Edit rule')">
      <x-slot:actions>
        <x-ui.button variant="ghost" :href="route('sca')">{{ __('Cancel') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
      <div class="ui:flex ui:flex-col ui:gap-4">
        <div class="ui:grid ui:gap-4 ui:sm:grid-cols-[2fr_1fr]">
          <x-ui.field :label="__('Name')" for="name">
            <x-ui.input id="name" value="{{ isset($check->id) ? $check->title : '' }}" :disabled="isset($check->id)"/>
          </x-ui.field>
          <x-ui.field :label="__('Platform')" for="platform">
            <x-ui.select id="platform">
              @foreach($platforms as $platform)
                <option value="{{ $platform->value }}" @selected($check->policy?->platform() === $platform)>{{ $platform->value }}</option>
              @endforeach
            </x-ui.select>
          </x-ui.field>
        </div>
        <x-ui.field :label="__('Description')" for="description">
          <x-ui.textarea id="description" rows="3">{{ $check->description }}</x-ui.textarea>
        </x-ui.field>
        <x-ui.field :label="__('Rationale')" for="rationale">
          <x-ui.textarea id="rationale" rows="3">{{ $check->rationale }}</x-ui.textarea>
        </x-ui.field>
        <x-ui.field :label="__('Remediation')" for="remediation">
          <x-ui.textarea id="remediation" rows="3">{{ $check->remediation }}</x-ui.textarea>
        </x-ui.field>
        <div class="ui:overflow-hidden ui:rounded-lg">
          <div id="editor" style="height:200px;width:100%;"></div>
        </div>
        <div class="ui:flex ui:justify-end ui:gap-2">
          @if(isset($check->id))
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
    editor.session.setMode("ace/mode/text");
    editor.setValue(@json($check->rule ?? ''));

    const btnDelete = document.querySelector('#delete-rule');
    const btnCreate = document.querySelector('#create-rule');
    const elName = document.querySelector('#name');
    const elDescription = document.querySelector('#description');
    const elRationale = document.querySelector('#rationale');
    const elRemediation = document.querySelector('#remediation');
    const elPlatform = document.querySelector('#platform');

    btnDelete?.addEventListener('click', () => {
      const response = confirm("{{ __('Are you sure you want to delete this rule?') }}");
      if (response) {
        deleteOssecRuleApiCall('{{ isset($check->id) ? $check->id : 0 }}');
      }
    });
    btnCreate.addEventListener('click', () => {
      createOssecRuleApiCall(elName.value, elDescription.value, elRationale.value, elRemediation.value, elPlatform.value,
        editor.getValue(), () => window.toaster.toastSuccess("{{ __('The rule has been saved.') }}"));
    });

  </script>
</x-layouts.app>

