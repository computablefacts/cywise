<?php

use App\Http\Controllers\Iframes\TableController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('table');
render(function (Request $request) {
  return app(TableController::class)($request);
});
?>

<x-layouts.app>
  @php
    // Wizard steps, in order. The JS below switches them by index (.step / .step-content).
    $steps = [__('Type'), __('Source'), __('Files'), __('Columns'), __('Query'), __('Done')];
    $radio = 'ui:m-0 ui:flex ui:cursor-pointer ui:items-center ui:gap-3 ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:px-4 ui:py-3 ui:text-sm ui:font-medium ui:text-ink ui:hover:bg-slate-50 ui:has-checked:border-brand-500 ui:has-checked:bg-brand-50';
    $check = 'ui:m-0 ui:flex ui:cursor-pointer ui:items-center ui:gap-2 ui:text-sm ui:text-ink';
    $box = 'ui:m-0 ui:size-4 ui:accent-brand-500';
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-5xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('New table')"
                      :subtitle="__('Import files or write a SQL query to create a table.')">
      <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('tables')">{{ __('Back to tables list') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    {{-- Step indicator: the active step (.active, set by goToStep) gets a filled brand circle. --}}
    <ol class="ui:m-0 ui:flex ui:list-none ui:items-start ui:gap-2 ui:p-0">
      @foreach($steps as $i => $label)
        <li class="step ui:group ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:items-center ui:gap-1.5 {{ $i === 0 ? 'active' : '' }}"
            data-step="{{ $i + 1 }}">
          <span class="ui:flex ui:size-7 ui:items-center ui:justify-center ui:rounded-full ui:bg-brand-50 ui:text-xs ui:font-semibold ui:text-brand-700 ui:ring-1 ui:ring-brand-200 ui:group-[.active]:bg-brand-500 ui:group-[.active]:text-white ui:group-[.active]:ring-brand-500">
            {{ $i + 1 }}
          </span>
          <span class="ui:max-w-full ui:truncate ui:text-xs ui:font-medium ui:text-slate-500 ui:group-[.active]:text-ink">{{ $label }}</span>
        </li>
      @endforeach
    </ol>

    {{-- Step 1: table kind --}}
    <div class="step-content active">
      <x-ui.card :title="__('1. What kind of table would you like to create?')">
        <div id="table-kinds-container" class="ui:grid ui:gap-3 ui:sm:grid-cols-2">
          <label class="{{ $radio }}">
            <input type="radio" name="table-kind" value="physical" class="{{ $box }}" checked/>
            {{ __('Physical') }}
          </label>
          <label class="{{ $radio }}">
            <input type="radio" name="table-kind" value="virtual" class="{{ $box }}"/>
            {{ __('Virtual') }}
          </label>
        </div>
        <div class="ui:mt-6 ui:flex ui:justify-end ui:gap-2">
          <x-ui.button class="next-button" data-next="2" icon="caret-right">{{ __('Next step') }}</x-ui.button>
        </div>
      </x-ui.card>
    </div>

    {{-- Step 2: storage and its credentials (one settings block shown at a time) --}}
    <div class="step-content ui:hidden">
      <x-ui.card :title="__('2.1 Where are the files you want to import?')">
        <div id="storage-kinds-container" class="ui:grid ui:gap-3 ui:sm:grid-cols-3">
          <label class="{{ $radio }}">
            <input type="radio" name="storage-kind" value="s3" class="{{ $box }}" checked/>
            {{ __('AWS S3 Bucket') }}
          </label>
          <label class="{{ $radio }}">
            <input type="radio" name="storage-kind" value="azure" class="{{ $box }}"/>
            {{ __('Azure Blob Storage') }}
          </label>
          <label class="{{ $radio }}">
            <input type="radio" name="storage-kind" value="local" class="{{ $box }}"/>
            {{ __('This computer (upload)') }}
          </label>
        </div>
        <div id="aws-settings" class="ui:mt-6">
          <h3 class="ui:m-0 ui:mb-3 ui:text-sm ui:font-semibold ui:text-ink">
            {{ __('2.2 What are the credentials for your AWS S3 Bucket?') }}
          </h3>
          <div class="ui:grid ui:gap-4 ui:sm:grid-cols-2">
            <x-ui.field :label="__('Region')" for="aws-region">
              <x-ui.input id="aws-region" placeholder="ex. eu-west-3"/>
            </x-ui.field>
            <x-ui.field :label="__('Access Key Id')" for="aws-access-key-id">
              <x-ui.input id="aws-access-key-id" placeholder="ex. AKIAIOSFODNN7EXAMPLE"/>
            </x-ui.field>
            <x-ui.field :label="__('Secret Access Key')" for="aws-secret-access-key" class="ui:sm:col-span-2">
              <x-ui.input id="aws-secret-access-key" placeholder="ex. wJalrXUtnFEMI/K7MDENG/bPxRfiCYzEXAMPLEKEY"/>
            </x-ui.field>
            <x-ui.field :label="__('Input Folder')" for="aws-input-folder">
              <x-ui.input id="aws-input-folder" placeholder="ex. my_s3_bucket/in/"/>
            </x-ui.field>
            <x-ui.field :label="__('Output Folder')" for="aws-output-folder">
              <x-ui.input id="aws-output-folder" placeholder="ex. my_s3_bucket/out/"/>
            </x-ui.field>
          </div>
        </div>
        <div id="azure-settings" class="ui:mt-6 ui:hidden">
          <h3 class="ui:m-0 ui:mb-3 ui:text-sm ui:font-semibold ui:text-ink">
            {{ __('2.2 What are the credentials for your Azure Blob Storage?') }}
          </h3>
          <div class="ui:grid ui:gap-4 ui:sm:grid-cols-2">
            <x-ui.field :label="__('Connection String')" for="azure-connection-string" class="ui:sm:col-span-2">
              <x-ui.input id="azure-connection-string"
                          placeholder="ex. DefaultEndpointsProtocol=https;AccountName=my_storage_account;AccountKey=my_account_key;EndpointSuffix=core.windows.net"/>
            </x-ui.field>
            <x-ui.field :label="__('Input Folder')" for="azure-input-folder">
              <x-ui.input id="azure-input-folder" placeholder="ex. my_container/in/"/>
            </x-ui.field>
            <x-ui.field :label="__('Output Folder')" for="azure-output-folder">
              <x-ui.input id="azure-output-folder" placeholder="ex. my_container/out/"/>
            </x-ui.field>
          </div>
        </div>
        <div id="upload-settings" class="ui:mt-6 ui:hidden">
          <h3 class="ui:m-0 ui:mb-3 ui:text-sm ui:font-semibold ui:text-ink">
            {{ __('2.2 Upload from this computer') }}
          </h3>
          <div id="upload-dropzone"
               class="ui:cursor-pointer ui:rounded-lg ui:border-2 ui:border-dashed ui:border-line ui:p-8 ui:text-center ui:text-sm ui:text-slate-500 ui:hover:border-brand-500">
            {{ __('Drag & drop TSV files here or click to browse') }}
          </div>
          <input id="upload-input" type="file" accept=".tsv,text/tab-separated-values" multiple class="ui:hidden"/>
        </div>
        <div class="ui:mt-6 ui:flex ui:justify-between ui:gap-2">
          <x-ui.button variant="secondary" class="prev-button" data-prev="1">{{ __('Previous step') }}</x-ui.button>
          <x-ui.button id="upload-table" class="next-button" data-next="3" icon="caret-right">{{ __('Next step') }}</x-ui.button>
        </div>
      </x-ui.card>
    </div>

    {{-- Step 3: files to import (rows filled by listTables) --}}
    <div class="step-content ui:hidden">
      <x-ui.card :title="__('3. Which table would you like to import?')" flush>
        <x-ui.table class="ui:table-fixed ui:[&_td]:truncate">
          <thead>
          <tr>
            <th class="ui:w-12"></th>
            <th>{{ __('Filename') }}</th>
            <th class="ui:text-right!">{{ __('File Size') }}</th>
            <th class="ui:text-right!">{{ __('Last Modified') }}</th>
          </tr>
          </thead>
          <tbody id="list-tables">
          <!-- FILLED DYNAMICALLY -->
          </tbody>
        </x-ui.table>
        <div class="ui:flex ui:justify-between ui:gap-2 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:p-5">
          <x-ui.button variant="secondary" class="prev-button" data-prev="2">{{ __('Previous step') }}</x-ui.button>
          <x-ui.button id="get-columns" class="next-button" data-next="4" icon="caret-right">{{ __('Next step') }}</x-ui.button>
        </div>
      </x-ui.card>
    </div>

    {{-- Step 4: description, import options and columns (rows filled by getTablesColumns) --}}
    <div class="step-content ui:hidden">
      <x-ui.card :title="__('4. Which columns would you like to retain?')" flush>
        <div class="ui:flex ui:flex-col ui:gap-4 ui:px-5 ui:pb-5">
          <x-ui.field :label="__('Description')" for="table-description">
            <x-ui.textarea id="table-description"
                           placeholder="{{ __('Please provide a detailed description of the table and explain the significance of the key columns. The more information you include, the better.') }}"/>
          </x-ui.field>
          <div class="ui:flex ui:flex-wrap ui:gap-x-6 ui:gap-y-2">
            <label for="toggle-columns-selection" class="{{ $check }}">
              <input type="checkbox" id="toggle-columns-selection" class="{{ $box }}"/>
              {{ __('Toggle selection') }}
            </label>
            <label for="toggle-deduplicate" class="{{ $check }}">
              <input type="checkbox" id="toggle-deduplicate" class="{{ $box }}" checked/>
              {{ __('Deduplicate rows') }}
            </label>
            <label for="toggle-copy" class="{{ $check }}">
              <input type="checkbox" id="toggle-copy" class="{{ $box }}"/>
              {{ __('Copy') }}
            </label>
            <label for="toggle-updatable" class="{{ $check }}">
              <input type="checkbox" id="toggle-updatable" class="{{ $box }}"/>
              {{ __('Update Automatically') }}
            </label>
          </div>
        </div>
        <x-ui.table class="ui:table-fixed ui:[&_td]:truncate">
          <thead>
          <tr>
            <th class="ui:w-12"></th>
            <th>{{ __('Filename') }}</th>
            <th>{{ __('Old Column Name') }}</th>
            <th>{{ __('New Column Name') }}</th>
            <th>{{ __('Column Type') }}</th>
          </tr>
          </thead>
          <tbody id="tables-columns">
          <!-- FILLED DYNAMICALLY -->
          </tbody>
        </x-ui.table>
        <div class="ui:flex ui:justify-between ui:gap-2 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:p-5">
          <x-ui.button variant="secondary" class="prev-button" data-prev="3">{{ __('Previous step') }}</x-ui.button>
          <x-ui.button id="import-tables" class="next-button" data-next="6" icon="caret-right">{{ __('Next step') }}</x-ui.button>
        </div>
      </x-ui.card>
    </div>

    {{-- Step 5: virtual table from a SQL query --}}
    <div class="step-content ui:hidden">
      <x-ui.card :title="__('5. Input the SQL query to generate a new virtual table.')">
        <div class="ui:flex ui:flex-col ui:gap-4">
          <x-ui.field :label="__('Name')" for="vtable-name">
            <x-ui.input id="vtable-name" placeholder="{{ __('The virtual table name such as active_users') }}"/>
          </x-ui.field>
          <x-ui.field :label="__('Description')" for="vtable-description">
            <x-ui.textarea id="vtable-description"
                           placeholder="{{ __('Please provide a detailed description of the table and explain the significance of the key columns. The more information you include, the better.') }}"/>
          </x-ui.field>
          <label for="toggle-materialize" class="{{ $check }}">
            <input type="checkbox" id="toggle-materialize" class="{{ $box }}"/>
            {{ __('Materialize') }}
          </label>
          <div>
            <x-sql-editor/>
          </div>
        </div>
        <div class="ui:mt-6 ui:flex ui:justify-between ui:gap-2">
          <x-ui.button variant="secondary" class="prev-button" data-prev="1">{{ __('Previous step') }}</x-ui.button>
          <x-ui.button id="create-vtable" class="next-button" data-next="6" icon="caret-right">{{ __('Next step') }}</x-ui.button>
        </div>
      </x-ui.card>
    </div>

    {{-- Step 6: done --}}
    <div class="step-content ui:hidden">
      <x-ui.card>
        <x-ui.empty icon="check-circle">{{ __('6. Your data will be accessible shortly!') }}</x-ui.empty>
        <!-- TODO : ADD IMAGE HERE -->
        <div class="ui:flex ui:justify-center">
          <x-ui.button :href="route('tables')">{{ __('Back to tables list') }}</x-ui.button>
        </div>
      </x-ui.card>
    </div>
  </div>
  <script>

    const steps = document.querySelectorAll('.step');
    const stepContents = document.querySelectorAll('.step-content');
    const nextButtons = document.querySelectorAll('.next-button');
    const prevButtons = document.querySelectorAll('.prev-button');
    const toggleColumnsSelection = document.getElementById('toggle-columns-selection');

    toggleColumnsSelection.onchange = () => {
      const checkboxes = document.querySelectorAll('#tables-columns input[type="checkbox"]');
      checkboxes.forEach(checkbox => checkbox.checked = !checkbox.checked);
    };

    nextButtons.forEach(button => {
      button.addEventListener('click', (event) => {
        const currentStep = parseInt(button.getAttribute('data-next')) - 1;
        let moveToNextStep = true;
        if (button.id === 'upload-table') {
          event.preventDefault();
          event.stopPropagation();
          if (elStorageType.el.selectedItem === LOCAL_STORAGE.value) {
            moveToNextStep = uploadTables();
          }
        } else if (button.id === 'get-columns') {
          event.preventDefault();
          event.stopPropagation();
          moveToNextStep = getTablesColumns();
        } else if (button.id === 'import-tables') {
          event.preventDefault();
          event.stopPropagation();
          moveToNextStep = importTables();
        } else if (button.id === 'create-vtable') {
          event.preventDefault();
          event.stopPropagation();
          moveToNextStep = createVirtualTables();
        }
        if (moveToNextStep) {
          goToStep(currentStep);
        }
      });
    });

    prevButtons.forEach(button => {
      button.addEventListener('click', () => {
        const prevStep = parseInt(button.getAttribute('data-prev')) - 1;
        goToStep(prevStep);
      });
    });

    const goToStep = (stepIndex) => {
      window.scrollTo(0, 0);
      if (stepIndex === 1 /* 0-based */ && elTableType.el.selectedItem === VIRTUAL_TABLE.value) {
        stepIndex = 4; // 0-based, when next is clicked bypass steps 2, 3 and 4
      }
      steps.forEach((step, index) => step.classList.toggle('active', index === stepIndex));
      stepContents.forEach((content, index) => {
        content.classList.toggle('active', index === stepIndex);
        content.classList.toggle('ui:hidden', index !== stepIndex);
      });
      if (stepIndex === 2 /* 0-based */) {
        listTables();
      }
    };

    const PHYSICAL_TABLE = {value: 'physical'};
    const VIRTUAL_TABLE = {value: 'virtual'};

    // Native radio group behind the BlueprintJS-like "el.selectedItem" read below, e.g. 'physical'.
    const radioGroup = (name) => ({
      el: {
        get selectedItem() {
          return document.querySelector(`input[name="${name}"]:checked`)?.value;
        }
      }
    });

    const elTableType = radioGroup('table-kind');

    const AWS_STORAGE = {value: 's3'};
    const AZURE_STORAGE = {value: 'azure'};
    const LOCAL_STORAGE = {value: 'local'};

    const elStorageType = radioGroup('storage-kind');

    const storageTypeButtons = document.querySelectorAll('#storage-kinds-container input');
    const awsSettings = document.getElementById('aws-settings');
    const azureSettings = document.getElementById('azure-settings');
    const uploadSettings = document.getElementById('upload-settings');

    storageTypeButtons.forEach(button => {
      button.addEventListener('change', () => {
        if (elStorageType.el.selectedItem === AWS_STORAGE.value) {
          console.log('AWS_STORAGE selected')
          azureSettings.classList.add('ui:hidden')
          uploadSettings.classList.add('ui:hidden')
          awsSettings.classList.remove('ui:hidden')
        }
        if (elStorageType.el.selectedItem === AZURE_STORAGE.value) {
          console.log('AZURE_STORAGE selected')
          awsSettings.classList.add('ui:hidden')
          uploadSettings.classList.add('ui:hidden')
          azureSettings.classList.remove('ui:hidden')
        }
        if (elStorageType.el.selectedItem === LOCAL_STORAGE.value) {
          console.log('LOCAL_STORAGE selected')
          awsSettings.classList.add('ui:hidden')
          azureSettings.classList.add('ui:hidden')
          uploadSettings.classList.remove('ui:hidden')
        }
      });
    });

    // Native inputs behind the BlueprintJS-like "el.value" read below.
    const textInput = (id) => ({el: document.getElementById(id)});

    const elAwsRegion = textInput('aws-region');
    const elAwsAccessKeyId = textInput('aws-access-key-id');
    const elAwsSecretAccessKey = textInput('aws-secret-access-key');
    const elAwsInputFolder = textInput('aws-input-folder');
    const elAwsOutputFolder = textInput('aws-output-folder');
    const elAzureConnectionString = textInput('azure-connection-string');
    const elAzureInputFolder = textInput('azure-input-folder');
    const elAzureOutputFolder = textInput('azure-output-folder');

    // Upload & Drag & Drop handlers
    const uploadDropzone = document.getElementById('upload-dropzone');
    const uploadInput = document.getElementById('upload-input');
    const uploadButton = document.getElementById('upload-button');
    let uploadSelectedFiles = [];
    let isUpdatingTheListOfTables = false;

    const refreshUploadList = () => {
      if (uploadSelectedFiles.length === 0) {
        uploadDropzone.innerHTML = "{{ __('Drag & drop TSV files here or click to browse') }}";
      } else {
        uploadDropzone.innerHTML = uploadSelectedFiles.map(f => f.name).join(', ')
          + "<br><br>{{ __('Drag & drop TSV files here or click to browse') }}";
      }
    }

    uploadDropzone.addEventListener('click', (e) => uploadInput.click());
    uploadDropzone.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadDropzone.classList.add('ui:bg-brand-50');
    });
    uploadDropzone.addEventListener('dragleave', () => uploadDropzone.classList.remove('ui:bg-brand-50'));
    uploadDropzone.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadDropzone.classList.remove('ui:bg-brand-50');
      const files = Array.from(e.dataTransfer.files || []);
      uploadSelectedFiles = uploadSelectedFiles.concat(files.filter(f => f.name.endsWith('.tsv')));
      refreshUploadList();
    });
    uploadInput.addEventListener('change', (e) => {
      const files = Array.from(e.target.files || []);
      uploadSelectedFiles = uploadSelectedFiles.concat(files.filter(f => f.name.endsWith('.tsv')));
      refreshUploadList();
    });

    const elVirtualTableName = textInput('vtable-name');

    const uploadTables = () => {
      if (uploadSelectedFiles.length === 0) {
        window.toaster.toastError("{{ __('Please select at least one TSV file.') }}");
        return false;
      }

      const form = new FormData();
      uploadSelectedFiles.forEach(f => form.append('files[]', f));
      isUpdatingTheListOfTables = true;

      axios.post('/api/tables/tsv/upload', form, {
        headers: {
          'Content-Type': 'multipart/form-data', 'Authorization': 'Bearer {{ Auth::user()->sentinelApiToken() }}',
        }
      })
        .then(response => {
          window.toaster.toastSuccess("{{ __('Files uploaded successfully!') }}");
          uploadSelectedFiles = [];
          refreshUploadList();
        })
        .catch(error => window.toaster.toastAxiosError(error))
        .finally(() => {
          isUpdatingTheListOfTables = false;
          listTables();
        });
      return true;
    };

    const listTables = () => {
      if (isUpdatingTheListOfTables) {
        return true;
      }

      const elListTables = document.getElementById('list-tables');
      elListTables.innerHTML = "<tr><td colspan=\"4\" class=\"ui:text-center ui:text-slate-500\">{{ __('Loading...') }}</td></tr>";

      if (elTableType.el.selectedItem === PHYSICAL_TABLE.value) {

        const onSuccess = response => {
          if (!response.files || response.files.length === 0) {
            elListTables.innerHTML = "<tr><td colspan=\"4\" class=\"ui:text-center ui:text-slate-500\">{{ __('No files found.') }}</td></tr>";
          } else {
            const rows = response.files.map(table => {
              return `
              <tr>
                <td><input type="checkbox" class="ui:m-0 ui:size-4 ui:accent-brand-500" value="${table.object}" data-file="${table.object}"/></td>
                <td>${table.object}</td>
                <td class="ui:text-right">${table.size}</td>
                <td class="ui:text-right">${table.last_modified}</td>
              </tr>
            `;
            });
            elListTables.innerHTML = rows.join('');
          }
        };

        if (elStorageType.el.selectedItem === AWS_STORAGE.value) {
          listAwsBucketContentApiCall(elAwsRegion.el.value, elAwsAccessKeyId.el.value, elAwsSecretAccessKey.el.value,
            elAwsInputFolder.el.value, elAwsOutputFolder.el.value, onSuccess);
        } else if (elStorageType.el.selectedItem === AZURE_STORAGE.value) {
          listAzureBucketContentApiCall(elAzureConnectionString.el.value, elAzureInputFolder.el.value,
            elAzureOutputFolder.el.value, onSuccess);
        } else if (elStorageType.el.selectedItem === LOCAL_STORAGE.value) {
          listLocalBucketContentApiCall(onSuccess);
        } else {
          // TODO
        }
      } else if (elTableType.el.selectedItem === VIRTUAL_TABLE.value) {
        // TODO
      } else {
        // TODO
      }
      return true;
    };

    const getTablesColumns = () => {

      const checkboxes = Array.from(document.querySelectorAll('#list-tables input[type="checkbox"]:checked'));
      const tables = checkboxes.map(checkbox => checkbox.getAttribute('data-file'));

      if (tables.length !== 1) {
        window.toaster.toastError("{{ __('Please select the table to import.') }}");
        return false;
      }

      const elTablesColumns = document.getElementById('tables-columns');
      elTablesColumns.innerHTML = "<tr><td colspan=\"5\" class=\"ui:text-center ui:text-slate-500\">{{ __('Loading...') }}</td></tr>";

      const onSuccess = response => {
        if (!response.tables || response.tables.length === 0) {
          elTablesColumns.innerHTML = "<tr><td colspan=\"5\" class=\"ui:text-center ui:text-slate-500\">{{ __('No columns found.') }}</td></tr>";
        } else {
          const rows = response.tables.flatMap(table => {
            return table.columns.map(column => {
              column.table = table.table;
              return `
              <tr>
                <td><input type="checkbox" class="ui:m-0 ui:size-4 ui:accent-brand-500" data-file="${com.computablefacts.helpers.toBase64(JSON.stringify(column))}" checked/></td>
                <td>${table.table}</td>
                <td>${column.old_name}</td>
                <td>${column.new_name}</td>
                <td>${column.type}</td>
              </tr>
            `;
            });
          });
          elTablesColumns.innerHTML = rows.join('');
        }
      };

      if (elStorageType.el.selectedItem === AWS_STORAGE.value) {
        listAwsFileContentApiCall(elAwsRegion.el.value, elAwsAccessKeyId.el.value, elAwsSecretAccessKey.el.value,
          elAwsInputFolder.el.value, elAwsOutputFolder.el.value, tables, onSuccess);
      } else if (elStorageType.el.selectedItem === AZURE_STORAGE.value) {
        listAzureFileContentApiCall(elAzureConnectionString.el.value, elAzureInputFolder.el.value,
          elAzureOutputFolder.el.value, tables, onSuccess);
      } else if (elStorageType.el.selectedItem === LOCAL_STORAGE.value) {
        listLocalFileContentApiCall(tables, onSuccess);
      } else {
        // TODO
      }
      return true;
    };

    const importTables = () => {

      const description = document.getElementById('table-description').value;
      const checkboxes = Array.from(document.querySelectorAll('#tables-columns input[type="checkbox"]:checked'));
      const tables = checkboxes.map(
        checkbox => JSON.parse(com.computablefacts.helpers.fromBase64(checkbox.getAttribute('data-file'))));

      if (tables.length === 0) {
        window.toaster.toastError("{{ __('Please select the table to import.') }}");
        return false;
      }
      if (description.trim() === '') {
        window.toaster.toastError("{{ __('Please enter a table description.') }}");
        return false;
      }

      const updatable = document.getElementById('toggle-updatable').checked === true;
      const copy = document.getElementById('toggle-copy').checked === true;
      const deduplicate = document.getElementById('toggle-deduplicate').checked === true;
      const onSuccess = response => window.toaster.toastSuccess(response.message);

      if (elStorageType.el.selectedItem === AWS_STORAGE.value) {
        importAwsFileApiCall(elAwsRegion.el.value, elAwsAccessKeyId.el.value, elAwsSecretAccessKey.el.value,
          elAwsInputFolder.el.value, elAwsOutputFolder.el.value, tables, updatable, copy, deduplicate, description,
          onSuccess);
      } else if (elStorageType.el.selectedItem === AZURE_STORAGE.value) {
        importAzureFileApiCall(elAzureConnectionString.el.value, elAzureInputFolder.el.value, elAzureOutputFolder.el.value,
          tables, updatable, copy, deduplicate, description, onSuccess);
      } else if (elStorageType.el.selectedItem === LOCAL_STORAGE.value) {
        importLocalFileApiCall(tables, updatable, copy, deduplicate, description, onSuccess);
      } else {
        // TODO
      }
      return true;
    };

    const createVirtualTables = () => {

      const description = document.getElementById('vtable-description').value;
      const name = elVirtualTableName.el.value;
      const sql = editor.getValue(); // from x-sql-editor
      const materialize = document.getElementById('toggle-materialize').checked === true;

      if (name.trim() === '') {
        window.toaster.toastError("{{ __('Please enter a table name.') }}");
        return false;
      }
      if (description.trim() === '') {
        window.toaster.toastError("{{ __('Please enter a table description.') }}");
        return false;
      }
      if (sql.trim() === '') {
        window.toaster.toastError("{{ __('Please enter a SQL query.') }}");
        return false;
      }

      createVirtualTableApiCall(sql, materialize, name, description, response => window.toaster.toastSuccess(response.message));
      return true;
    };

  </script>
</x-layouts.app>

