@extends('theme::iframes.app')

@push('styles')
<style>

  table {
    table-layout: fixed;
  }

  td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .steps {
    display: flex;
    justify-content: space-between;
  }

  .step {
    padding: 10px 15px;
    color: var(--ds-background-brand-bold);
    flex: 1;
    text-align: center;
  }

  .step.active {
    background: var(--ds-background-brand-bold);
    color: white;
    font-weight: bold;
  }

  .content {
    background-color: white;
  }

  .step-content {
    display: none;
  }

  .step-content.active {
    display: block;
  }

  .hidden {
    display: none;
  }

</style>
@endpush

@section('content')
<div class="steps mt-3">
  <div class="step active" data-step="1">
    {{ __('Step 1') }}
  </div>
  <div class="step" data-step="2">
    {{ __('Step 2') }}
  </div>
  <div class="step" data-step="3">
    {{ __('Step 3') }}
  </div>
  <div class="step" data-step="4">
    {{ __('Step 4') }}
  </div>
  <div class="step" data-step="5">
    {{ __('Step 5') }}
  </div>
  <div class="step" data-step="6">
    {{ __('Step 6') }}
  </div>
</div>
<div class="content my-3">
  <div class="card step-content active">
    <div class="card-body">
      <h5 class="card-title">
        {{ __('1. What kind of table would you like to create?') }}
      </h5>
      <div class="row mt-2">
        <div class="col">
          <div id="table-kinds-container"></div>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col text-center">
          <button class="btn btn-primary next-button" data-next="2">{{ __('Next step >') }}</button>
        </div>
      </div>
    </div>
  </div>
  <div class="card step-content">
    <div class="card-body">
      <h5 class="card-title">
        {{ __('2.1 Where are the files you want to import?') }}
      </h5>
      <div class="row mt-2">
        <div class="col">
          <div id="storage-kinds-container"></div>
        </div>
      </div>
      <div id="aws-settings">
        <div class="row mt-2">
          <div class="col">
            <h5>
              {{ __('2.2 What are the credentials for your AWS S3 Bucket?') }}
            </h5>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 align-content-center text-end">
            <b>{{ __('Region') }}</b>
          </div>
          <div class="col">
            <div id="aws-region"></div>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 align-content-center text-end">
            <b>{{ __('Access Key Id') }}</b>
          </div>
          <div class="col">
            <div id="aws-access-key-id"></div>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 align-content-center text-end">
            <b>{{ __('Secret Access Key') }}</b>
          </div>
          <div class="col">
            <div id="aws-secret-access-key"></div>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 align-content-center text-end">
            <b>{{ __('Input Folder') }}</b>
          </div>
          <div class="col">
            <div id="aws-input-folder"></div>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 align-content-center text-end">
            <b>{{ __('Output Folder') }}</b>
          </div>
          <div class="col">
            <div id="aws-output-folder"></div>
          </div>
        </div>
      </div>
      <div id="azure-settings" class="hidden">
        <div class="row mt-2">
          <div class="col">
            <h5 class="card-title">
              {{ __('2.2 What are the credentials for your Azure Blob Storage?') }}
            </h5>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 align-content-center text-end">
            <b>{{ __('Connection String') }}</b>
          </div>
          <div class="col">
            <div id="azure-connection-string"></div>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 align-content-center text-end">
            <b>{{ __('Input Folder') }}</b>
          </div>
          <div class="col">
            <div id="azure-input-folder"></div>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 align-content-center text-end">
            <b>{{ __('Output Folder') }}</b>
          </div>
          <div class="col">
            <div id="azure-output-folder"></div>
          </div>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col text-center">
          <button class="btn btn-primary prev-button" data-prev="1">{{ __('< Previous step') }}</button>
          <button class="btn btn-primary next-button" data-next="3">{{ __('Next step >') }}</button>
        </div>
      </div>
    </div>
  </div>
  <div class="card step-content">
    <div class="card-body">
      <h5 class="card-title">
        {{ __('3. Which table would you like to import?') }}
      </h5>
      <div class="row mt-2">
        <div class="col">
          <table class="table">
            <thead>
            <tr>
              <th style="width:30px"></th>
              <th>{{ __('Filename') }}</th>
              <th class="text-end">{{ __('File Size') }}</th>
              <th class="text-end">{{ __('Last Modified') }}</th>
            </tr>
            </thead>
            <tbody id="list-tables">
            <!-- FILLED DYNAMICALLY -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col text-center">
          <button class="btn btn-primary prev-button" data-prev="2">{{ __('< Previous step') }}</button>
          <button id="get-columns" class="btn btn-primary next-button" data-next="4">{{ __('Next step >') }}</button>
        </div>
      </div>
    </div>
  </div>
  <div class="card step-content">
    <div class="card-body">
      <h5 class="card-title">
        {{ __('4. Which columns would you like to retain?') }}
      </h5>
      <div class="row mt-2">
        <div class="col">
          <textarea id="table-description"
                    class="form-control mt-2"
                    rows="4"
                    placeholder="{{ __('Please provide a detailed description of the table and explain the significance of the key columns. The more information you include, the better.') }}"></textarea>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col">
          <input type="checkbox" id="toggle-columns-selection"/>
          <label for="toggle-columns-selection">{{ __('Toggle selection') }}</label>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col">
          <input type="checkbox" id="toggle-deduplicate" checked/>
          <label for="toggle-deduplicate">{{ __('Deduplicate rows') }}</label>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col">
          <input type="checkbox" id="toggle-copy"/>
          <label for="toggle-copy">{{ __('Copy') }}</label>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col">
          <input type="checkbox" id="toggle-updatable"/>
          <label for="toggle-updatable">{{ __('Update Automatically') }}</label>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col">
          <table class="table">
            <thead>
            <tr>
              <th style="width:30px"></th>
              <th>{{ __('Filename') }}</th>
              <th>{{ __('Old Column Name') }}</th>
              <th>{{ __('New Column Name') }}</th>
              <th>{{ __('Column Type') }}</th>
            </tr>
            </thead>
            <tbody id="tables-columns">
            <!-- FILLED DYNAMICALLY -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col text-center">
          <button class="btn btn-primary prev-button" data-prev="3">{{ __('< Previous step') }}</button>
          <button id="import-tables" class="btn btn-primary next-button" data-next="6">{{ __('Next step >') }}</button>
        </div>
      </div>
    </div>
  </div>
  <div class="card step-content">
    <div class="card-body">
      <h5 class="card-title">
        {{ __('5. Input the SQL query to generate a new virtual table.') }}
      </h5>
      <div class="row mt-2">
        <div class="col">
          <div id="vtable-name"></div>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col">
          <textarea id="vtable-description"
                    class="form-control mt-2"
                    rows="4"
                    placeholder="{{ __('Please provide a detailed description of the table and explain the significance of the key columns. The more information you include, the better.') }}"></textarea>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col">
          <input type="checkbox" id="toggle-materialize"/>
          <label for="toggle-materialize">{{ __('Materialize') }}</label>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col">
          <x-sql-editor/>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col text-center">
          <button class="btn btn-primary prev-button" data-prev="1">{{ __('< Previous step') }}</button>
          <button id="create-vtable" class="btn btn-primary next-button" data-next="6">{{ __('Next step >') }}</button>
        </div>
      </div>
    </div>
  </div>
  <div class="card step-content">
    <div class="card-body">
      <h5 class="card-title">
        {{ __('6. Your data will be accessible shortly!') }}
      </h5>
      <div class="row mt-2">
        <div class="col text-center">
          <!-- TODO : ADD IMAGE HERE -->
        </div>
      </div>
      <div class="row mt-2">
        <div class="col text-center">
          <a class="btn btn-primary" href="{{ route('iframes.tables') }}">{{ __('Back to tables list') }}</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')

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
      if (event.target && event.target.id === 'get-columns') {
        event.preventDefault();
        event.stopPropagation();
        moveToNextStep = getTablesColumns();
      } else if (event.target && event.target.id === 'import-tables') {
        event.preventDefault();
        event.stopPropagation();
        moveToNextStep = importTables();
      } else if (event.target && event.target.id === 'create-vtable') {
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
    stepContents.forEach((content, index) => content.classList.toggle('active', index === stepIndex));
    if (stepIndex === 2 /* 0-based */) {
      listTables();
    }
  };

  const PHYSICAL_TABLE = {
    label: "{{ __('Physical') }}", value: 'physical'
  };
  const VIRTUAL_TABLE = {
    label: "{{ __('Virtual') }}", value: 'virtual'
  };

  const elTableType = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'RadioGroup',
    container: 'table-kinds-container',
    inline: false,
    items: [PHYSICAL_TABLE, VIRTUAL_TABLE],
    selected_item: PHYSICAL_TABLE.value,
  });

  const AWS_STORAGE = {
    label: "{{ __('AWS S3 Bucket') }}", value: 's3'
  };
  const AZURE_STORAGE = {
    label: "{{ __('Azure Blob Storage') }}", value: 'azure'
  };

  const elStorageType = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'RadioGroup',
    container: 'storage-kinds-container',
    inline: false,
    items: [AWS_STORAGE, AZURE_STORAGE],
    selected_item: AWS_STORAGE.value,
  });

  const storageTypeButtons = document.querySelectorAll('#storage-kinds-container input');
  const awsSettings = document.getElementById('aws-settings');
  const azureSettings = document.getElementById('azure-settings');

  storageTypeButtons.forEach(button => {
    button.addEventListener('change', () => {
      if (elStorageType.el.selectedItem === AWS_STORAGE.value) {
        console.log('AWS_STORAGE selected')
        azureSettings.classList.add('hidden')
        awsSettings.classList.remove('hidden')
      }
      if (elStorageType.el.selectedItem === AZURE_STORAGE.value) {
        console.log('AZURE_STORAGE selected')
        awsSettings.classList.add('hidden')
        azureSettings.classList.remove('hidden')
      }
    });
  });

  const elAwsRegion = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'TextInput', container: 'aws-region', placeholder: 'ex. eu-west-3'
  });

  const elAwsAccessKeyId = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'TextInput', container: 'aws-access-key-id', placeholder: 'ex. AKIAIOSFODNN7EXAMPLE',
  });

  const elAwsSecretAccessKey = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'TextInput', container: 'aws-secret-access-key', placeholder: 'ex. wJalrXUtnFEMI/K7MDENG/bPxRfiCYzEXAMPLEKEY',
  });

  const elAwsInputFolder = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'TextInput', container: 'aws-input-folder', placeholder: 'ex. my_s3_bucket/in/',
  });

  const elAwsOutputFolder = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'TextInput', container: 'aws-output-folder', placeholder: 'ex. my_s3_bucket/out/',
  });

  const elAzureConnectionString = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'TextInput',
    container: 'azure-connection-string',
    placeholder: 'ex. DefaultEndpointsProtocol=https;AccountName=my_storage_account;AccountKey=my_account_key;EndpointSuffix=core.windows.net',
  });

  const elAzureInputFolder = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'TextInput', container: 'azure-input-folder', placeholder: 'ex. my_container/in/',
  });

  const elAzureOutputFolder = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'TextInput', container: 'azure-output-folder', placeholder: 'ex. my_container/out/',
  });

  const elVirtualTableName = com.computablefacts.blueprintjs.Blueprintjs.component(document, {
    type: 'TextInput', container: 'vtable-name', placeholder: "{{ __('The virtual table name such as active_users') }}"
  });

  const listTables = () => {

    const elListTables = document.getElementById('list-tables');
    elListTables.innerHTML = "<tr><td colspan=\"4\" class=\"text-center\">{{ __('Loading...') }}</td></tr>";

    if (elTableType.el.selectedItem === PHYSICAL_TABLE.value) {

      const onSuccess = response => {
        if (!response.files || response.files.length === 0) {
          elListTables.innerHTML = "<tr><td colspan=\"4\" class=\"text-center\">{{ __('No files found.') }}</td></tr>";
        } else {
          const rows = response.files.map(table => {
            return `
              <tr>
                <td><input type="checkbox" value="${table.object}" data-file="${table.object}"/></td>
                <td>${table.object}</td>
                <td class="text-end">${table.size}</td>
                <td class="text-end">${table.last_modified}</td>
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
        listAzureBucketContentApiCall(elAzureConnectionString.el.value, elAwsInputFolder.el.value,
          elAwsOutputFolder.el.value, onSuccess);
      } else {
        // TODO
      }
    } else if (elTableType.el.selectedItem === VIRTUAL_TABLE.value) {
      // TODO
    } else {
      // TODO
    }
  };

  const getTablesColumns = () => {

    const checkboxes = Array.from(document.querySelectorAll('#list-tables input[type="checkbox"]:checked'));
    const tables = checkboxes.map(checkbox => checkbox.getAttribute('data-file'));

    if (tables.length !== 1) {
      toaster.toastError("{{ __('Please select the table to import.') }}");
      return false;
    }

    const elTablesColumns = document.getElementById('tables-columns');
    elTablesColumns.innerHTML = "<tr><td colspan=\"5\" class=\"text-center\">{{ __('Loading...') }}</td></tr>";

    const onSuccess = response => {
      if (!response.tables || response.tables.length === 0) {
        elTablesColumns.innerHTML = "<tr><td colspan=\"5\" class=\"text-center\">{{ __('No columns found.') }}</td></tr>";
      } else {
        const rows = response.tables.flatMap(table => {
          return table.columns.map(column => {
            column.table = table.table;
            return `
              <tr>
                <td><input type="checkbox" data-file="${com.computablefacts.helpers.toBase64(JSON.stringify(column))}" checked/></td>
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
      listAzureFileContentApiCall(elAzureConnectionString.el.value, elAwsInputFolder.el.value,
        elAwsOutputFolder.el.value, tables, onSuccess);
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
      toaster.toastError("{{ __('Please select the table to import.') }}");
      return false;
    }
    if (description.trim() === '') {
      toaster.toastError("{{ __('Please enter a table description.') }}");
      return false;
    }

    const updatable = document.getElementById('toggle-updatable').checked === true;
    const copy = document.getElementById('toggle-copy').checked === true;
    const deduplicate = document.getElementById('toggle-deduplicate').checked === true;
    const onSuccess = response => toaster.toastSuccess(response.message);

    if (elStorageType.el.selectedItem === AWS_STORAGE.value) {
      importAwsFileApiCall(elAwsRegion.el.value, elAwsAccessKeyId.el.value, elAwsSecretAccessKey.el.value,
        elAwsInputFolder.el.value, elAwsOutputFolder.el.value, tables, updatable, copy, deduplicate, description,
        onSuccess);
    } else if (elStorageType.el.selectedItem === AZURE_STORAGE.value) {
      importAzureFileApiCall(elAzureConnectionString.el.value, elAwsInputFolder.el.value, elAwsOutputFolder.el.value,
        tables, updatable, copy, deduplicate, description, onSuccess);
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
      toaster.toastError("{{ __('Please enter a table name.') }}");
      return false;
    }
    if (description.trim() === '') {
      toaster.toastError("{{ __('Please enter a table description.') }}");
      return false;
    }
    if (sql.trim() === '') {
      toaster.toastError("{{ __('Please enter a SQL query.') }}");
      return false;
    }

    createVirtualTableApiCall(sql, materialize, name, description, response => toaster.toastSuccess(response.message));
    return true;
  };

</script>
@endpush