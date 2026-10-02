<?php

use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use function Laravel\Folio\{middleware, name};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('cyberscribe');
?>

<x-layouts.app>
  {{--
    header                        [import] [export] [clear] [delete] [Save]
    template picker  |  import your own template (.json)  [Submit]
    contents | document (BlockNote)
    Blueprint widgets (#templates, #file, #submit) and the editor are mounted by the script below.
  --}}
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header title="CyberScribe" :subtitle="__('Write your security charters and policies from a template, with the help of AI.')">
      <x-slot:actions>
        <x-ui.icon-button icon="upload-simple" :title="__('Import a Markdown document')" onclick="importDocument()"/>
        <x-ui.icon-button icon="download-simple" :title="__('Export as Markdown')" onclick="exportDocument()"/>
        <x-ui.icon-button icon="eraser" :title="__('Clear the editor')" onclick="clearDocument()"/>
        <x-ui.icon-button icon="trash" :title="__('Delete the document')" onclick="deleteDocument()"/>
        <x-ui.button icon="floppy-disk" onclick="saveDocument()">{{ __('Save') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    <div class="ui:grid ui:items-end ui:gap-4 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:p-4 ui:shadow-xs ui:lg:grid-cols-2">
      <x-ui.field :label="__('Template or document')">
        <div id="templates"></div>
      </x-ui.field>
      <x-ui.field :label="__('Import your own template (.json)')">
        <div class="ui:flex ui:items-center ui:gap-2">
          <div id="file" class="ui:min-w-0 ui:flex-1"></div>
          <div id="submit"></div>
        </div>
      </x-ui.field>
    </div>

    <x-block-note/>
  </div>

  <style>
    /* Blueprint widgets: full width, aligned with the design system inputs */
    #templates .bp4-popover2-target, #templates .bp4-button, #file .bp4-file-input { width: 100%; }
    #templates .bp4-button, #file .bp4-file-input, #submit .bp4-button { min-height: 2.5rem; }
    /* Editor typography aligned with the app: Inter, bold headings */
    #block-note .bn-container { --bn-font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    #block-note [data-content-type="heading"] .bn-inline-content { font-weight: 700; letter-spacing: -0.01em; color: var(--ui-color-ink); }
    /* Keep headings clear of the sticky topbar when jumping from the table of contents */
    #block-note [data-id] { scroll-margin-top: 5rem; }
  </style>
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
          window.toaster.toastSuccess("{{ __('Your model has been successfully uploaded!') }}");
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
        window.toaster.toastError("{{ __('The document is not loaded!') }}");
        return;
      }
      saveTemplateApiCall(template.id, false, template.name, ctx.blocks, response => {
        if (!template.id) {
          template.type = response.template.type;
        }
        template.id = response.template.id;
        elTemplates.items = [response.template].concat(elTemplates.items.filter(item => item.id !== template.id)); // TODO : sort by name?
        elTemplates.selectedItem = response.template;
        window.toaster.toastSuccess("{{ __('The document has been saved!') }}");
      });
    };

    const exportDocument = () => {

      const ctx = window.BlockNote ? window.BlockNote.ctx : null;

      if (!ctx) {
        window.toaster.toastError("{{ __('The document is not loaded!') }}");
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
          window.toaster.toastSuccess("{{ __('The document has been deleted!') }}");
        });
      }
    };

  </script>
</x-layouts.app>

