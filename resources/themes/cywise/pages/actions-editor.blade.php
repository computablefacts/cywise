<?php

use App\Http\Controllers\Iframes\ActionsEditorController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('actions.editor');
render(function (Request $request) {
  return app(ActionsEditorController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-4xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Edit action')"
                      :subtitle="__('Remote action: an HTTP endpoint CyberBuddy may call.')">
      <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('actions')">{{ __('Back to actions') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    {{-- Fields read by the script below (ids), JSON fields are Ace editors --}}
    <x-ui.card>
      <div class="ui:flex ui:flex-col ui:gap-5">
        <x-ui.field :label="__('Name')" for="name">
          <x-ui.input id="name" value="{{ $action->name }}"/>
        </x-ui.field>
        <x-ui.field :label="__('Description')" for="description">
          <x-ui.textarea id="description" rows="3">{{ $action->description }}</x-ui.textarea>
        </x-ui.field>
        <x-ui.field :label="__('URL')" for="url">
          <x-ui.input id="url" value="{{ $action->url }}"/>
        </x-ui.field>
        <x-ui.field :label="__('Headers (JSON)')">
          <div id="editor-headers" class="ui:overflow-hidden ui:rounded-lg" style="height:100px;width:100%;"></div>
        </x-ui.field>
        <x-ui.field :label="__('Schema (JSON)')">
          <div id="editor-schema" class="ui:overflow-hidden ui:rounded-lg" style="height:150px;width:100%;"></div>
        </x-ui.field>
        <x-ui.field :label="__('Payload Template (JSON)')">
          <div id="editor-payload" class="ui:overflow-hidden ui:rounded-lg" style="height:150px;width:100%;"></div>
        </x-ui.field>
        <x-ui.field :label="__('Response Template')" for="response_template">
          <x-ui.textarea id="response_template" rows="3">{{ $action->response_template }}</x-ui.textarea>
        </x-ui.field>
        <x-ui.field :label="__('Examples (JSON)')">
          <div id="editor-examples" class="ui:overflow-hidden ui:rounded-lg" style="height:150px;width:100%;"></div>
        </x-ui.field>
      </div>
    </x-ui.card>

    {{-- Delete hidden on a new action (no id yet) --}}
    <div class="ui:flex ui:justify-end ui:gap-2">
      <x-ui.button id="delete-action" variant="secondary" :style="isset($action->id) ? null : 'display: none;'">
        {{ __('Delete') }}
      </x-ui.button>
      <x-ui.button id="save-action">
        {{ __('Save') }}
      </x-ui.button>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.6.0/ace.js"></script>
  <script>

    function createEditor(id, value, mode = "ace/mode/json") {
      const editor = ace.edit(id);
      editor.setTheme("ace/theme/monokai");
      editor.session.setMode(mode);
      editor.setValue(value ? JSON.stringify(value, null, 2) : '');
      editor.clearSelection();
      return editor;
    }

    const editorHeaders = createEditor("editor-headers", @json($action->headers));
    const editorSchema = createEditor("editor-schema", @json($action->schema));
    const editorPayload = createEditor("editor-payload", @json($action->payload_template));
    const editorExamples = createEditor("editor-examples", @json($action->examples));

    const btnDelete = document.querySelector('#delete-action');
    const btnSave = document.querySelector('#save-action');
    const elName = document.querySelector('#name');
    const elDescription = document.querySelector('#description');
    const elUrl = document.querySelector('#url');
    const elResponseTemplate = document.querySelector('#response_template');

    btnDelete.addEventListener('click', () => {
      const response = confirm("{{ __('Are you sure you want to delete this action?') }}");
      if (response) {
        deleteRemoteActionApiCall('{{ isset($action->id) ? $action->id : 0 }}', () => {
          window.location.href = "{{ route('actions') }}";
        });
      }
    });

    btnSave.addEventListener('click', () => {

      const params = {
        name: elName.value,
        description: elDescription.value,
        url: elUrl.value,
        headers: JSON.parse(editorHeaders.getValue() || '{}'),
        schema: JSON.parse(editorSchema.getValue() || '{}'),
        payload_template: JSON.parse(editorPayload.getValue() || '{}'),
        response_template: elResponseTemplate.value,
        examples: JSON.parse(editorExamples.getValue() || '[]')
      };

      createRemoteActionApiCall(params, () => window.toaster.toastSuccess("{{ __('The action has been saved.') }}"));
    });

  </script>
</x-layouts.app>
