@extends('theme::iframes.app')

@section('content')
<div class="card mt-3 mb-3">
  <div class="card-body">
    <div class="row">
      <div class="col">
        <div id="templates" class="mb-3"></div>
      </div>
    </div>
    <div class="row">
      <div class="col-10">
        <div id="file"></div>
      </div>
      <div class="col">
        <div id="submit"></div>
      </div>
    </div>
  </div>
</div>
<div class="container-fluid mb-3">
  <div class="row">
    <div class="col text-end">
      <span class="bp4-icon bp4-icon-eraser"></span>&nbsp;<a href="#" onclick="clearDocument()">
        {{ __('clear') }}
      </a>&nbsp;&nbsp;&nbsp;
      <span class="bp4-icon bp4-icon-import"></span>&nbsp;<a href="#" onclick="importDocument()">
        {{ __('import') }}
      </a>&nbsp;&nbsp;&nbsp;
      <span class="bp4-icon bp4-icon-export"></span>&nbsp;<a href="#" onclick="exportDocument()">
        {{ __('export') }}
      </a>&nbsp;&nbsp;&nbsp;
      <span class="bp4-icon bp4-icon-trash"></span>&nbsp;<a href="#" onclick="deleteDocument()">
        {{ __('delete') }}
      </a>&nbsp;&nbsp;&nbsp;
      <span class="bp4-icon bp4-icon-floppy-disk"></span>&nbsp;<a href="#" onclick="saveDocument()">
        {{ __('save') }}
      </a>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-body p-2">
    <x-block-note/>
  </div>
</div>
@endsection

@push('scripts')
@viteReactRefresh
@vite('resources/js/app.js')
<script>

  let files = null;

  const elTemplates = new com.computablefacts.blueprintjs.MinimalSelect(document.getElementById('templates'),
    item => item.name, item => item.type === 'template' ? item.type : `${item.type} (${item.user})`, null,
    query => query);
  elTemplates.onSelectionChange(template => {
    if (window.BlockNote.observers) {
      window.BlockNote.template = template;
      window.BlockNote.observers.notify('template-change', template);
    }
  });
  elTemplates.defaultText = "{{ __('Load template...') }}";

  const elSubmit = new com.computablefacts.blueprintjs.MinimalButton(document.getElementById('submit'),
    "{{ __('Submit') }}");
  elSubmit.disabled = true;
  elSubmit.onClick(() => {

    elSubmit.loading = true;
    elSubmit.disabled = true;

    const file = files[0];
    const reader = new FileReader();
    reader.onload = e => {
      saveTemplateApiCall(null, true, file.name, JSON.parse(e.target.result), response => {
        elTemplates.items = [response.template].concat(elTemplates.items); // TODO : sort by name?
        toaster.toastSuccess("{{ __('Your model has been successfully uploaded!') }}");
      }, () => {
        elSubmit.loading = false;
        elSubmit.disabled = false;
      });
    };
    reader.readAsText(file);
  });

  const elFile = new com.computablefacts.blueprintjs.MinimalFileInput(document.getElementById('file'), true);
  elFile.onSelectionChange(items => {
    files = items;
    elSubmit.disabled = !files;
  });
  elFile.text = "{{ __('Import your own template...') }}";
  elFile.buttonText = "{{ __('Browse') }}";

  document.addEventListener('DOMContentLoaded',
    (event) => listTemplatesApiCall(response => elTemplates.items = response.templates));

  const documentCannotBeDeleted = () => !elTemplates.selectedItem || !elTemplates.selectedItem.id
    || elTemplates.selectedItem.type === 'template';

  const saveDocument = () => {

    const template = window.BlockNote ? window.BlockNote.template : null;
    const ctx = window.BlockNote ? window.BlockNote.ctx : null;

    if (!template || !ctx) {
      toaster.toastError("{{ __('The document is not loaded!') }}");
      return;
    }
    saveTemplateApiCall(template.id, false, template.name, ctx.blocks, response => {
      if (!template.id) {
        template.type = response.template.type;
      }
      template.id = response.template.id;
      elTemplates.items = [response.template].concat(elTemplates.items.filter(item => item.id !== template.id)); // TODO : sort by name?
      elTemplates.selectedItem = response.template;
      toaster.toastSuccess("{{ __('The document has been saved!') }}");
    });
  };

  const exportDocument = () => {

    const ctx = window.BlockNote ? window.BlockNote.ctx : null;

    if (!ctx) {
      toaster.toastError("{{ __('The document is not loaded!') }}");
      return;
    }

    const editor = ctx.editor;
    const blocks = ctx.blocks;
    const markdownContent = editor.blocksToMarkdownLossy(blocks);

    markdownContent.then(md => {
      const blob = new Blob([md], {type: 'text/markdown'});
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'draft.md';
      link.click();
      window.URL.revokeObjectURL(url);
    });
  };

  const importDocument = () => {

    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.md';
    input.onchange = event => {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => {
          const markdownContent = e.target.result;
          const editor = window.BlockNote.ctx.editor;
          const blocksFromMarkdown = editor.tryParseMarkdownToBlocks(markdownContent);
          blocksFromMarkdown.then(blocks => {
            listCollectionsApiCall(response => {
              const collections = response.collections.map(collection => collection.name);
              for (let i = 0; i < blocks.length; i++) {
                const block = blocks[i];
                if (block.type === 'paragraph') {
                  if (block.content.length === 1) {
                    for (let j = 0; j < block.content.length; j++) {
                      if (block.content[j].type === 'text') {
                        const text = block.content[j].text.trim();
                        if (text.startsWith('Q:')) {
                          blocks[i] = {
                            id: block.id, type: "ai_block", props: {
                              prompt: text.substring(2), collection: collections[0], collections: collections,
                            }, content: []
                          };
                        }
                      }
                    }
                  }
                }
              }
              const template = {
                name: file.name.slice(0, -3), template: blocks, type: 'draft', user: '{{ Auth::user()->name }}',
              };
              window.BlockNote.template = template;
              window.BlockNote.observers.notify('template-change', template);
            });
          });
        };
        reader.readAsText(file);
      }
    };
    input.click();
  };

  const clearDocument = () => {
    window.BlockNote.template = null;
    window.BlockNote.observers.notify('template-change', null);
    elTemplates.selectedItem = null;
  };

  const deleteDocument = () => {
    if (documentCannotBeDeleted()) {
      clearDocument();
    } else {
      deleteTemplateApiCall(elTemplates.selectedItem.id, () => {
        elTemplates.items = elTemplates.items.filter(item => item.id !== elTemplates.selectedItem.id);
        clearDocument();
        toaster.toastSuccess("{{ __('The document has been deleted!') }}");
      });
    }
  };

</script>
@endpush