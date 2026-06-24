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
  <div class="container-fluid">
    <div class="card mt-3 mb-3">
      <div class="card-body">
        <h6 class="card-title text-truncate">{{ __('Edit action') }}</h6>
        <div class="form-group">
          <div class="mb-3">
            <label for="name" class="form-label">{{ __('Name') }}</label>
            <input id="name" class="form-control" value="{{ $action->name }}">
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">{{ __('Description') }}</label>
            <textarea id="description" class="form-control" rows="3">{{ $action->description }}</textarea>
          </div>
          <div class="mb-3">
            <label for="url" class="form-label">{{ __('URL') }}</label>
            <input id="url" class="form-control" value="{{ $action->url }}">
          </div>
          <div class="mb-3">
            <label for="headers" class="form-label">{{ __('Headers (JSON)') }}</label>
            <div id="editor-headers" style="height:100px;width:100%;"></div>
          </div>
          <div class="mb-3">
            <label for="schema" class="form-label">{{ __('Schema (JSON)') }}</label>
            <div id="editor-schema" style="height:150px;width:100%;"></div>
          </div>
          <div class="mb-3">
            <label for="payload_template" class="form-label">{{ __('Payload Template (JSON)') }}</label>
            <div id="editor-payload" style="height:150px;width:100%;"></div>
          </div>
          <div class="mb-3">
            <label for="response_template" class="form-label">{{ __('Response Template') }}</label>
            <textarea id="response_template" class="form-control" rows="3">{{ $action->response_template }}</textarea>
          </div>
          <div class="mb-3">
            <label for="examples" class="form-label">{{ __('Examples (JSON)') }}</label>
            <div id="editor-examples" style="height:150px;width:100%;"></div>
          </div>
          <div class="mb-3">
            <div class="col text-center">
              <button id="delete-action" class="btn btn-danger {{ isset($action->id) ? '' : 'd-none' }}">
                {{ __('Delete') }}
              </button>
              <button id="save-action" class="btn btn-primary">
                {{ __('Save') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
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
  @endpush

</x-layouts.app>
