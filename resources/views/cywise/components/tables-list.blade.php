<table class="table table-hover mb-0">
  <thead>
  <tr>
    <th>{{ __('Table') }}</th>
    <th class="text-end">{{ __('Number of Rows') }}</th>
    <th class="text-end">{{ __('Number of Columns') }}</th>
    <th>{{ __('Description') }}</th>
    <th>{{ __('Last Update') }}</th>
    <th>{{ __('Status') }}</th>
  </tr>
  </thead>
  <tbody id="databases-and-tables">
  <!-- FILLED DYNAMICALLY -->
  </tbody>
</table>
<script>

  const escapeHtml = (str) => str
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#039;');

  const escapeAttr = (str) => escapeHtml(str).replace(/`/g, '&#96;');
  const qsClosest = (el, selector) => el.closest(selector);

  const startEditDescription = (anchor) => {
    const td = qsClosest(anchor, 'td');
    const view = td.querySelector('.desc-view');
    const edit = td.querySelector('.desc-edit');
    const input = edit.querySelector('input');
    input.value = view.querySelector('.desc-text').textContent;
    view.classList.add('d-none');
    edit.classList.remove('d-none');
    input.focus();
    return false;
  }

  const cancelEditDescription = (btn) => {
    const td = qsClosest(btn, 'td');
    td.querySelector('.desc-edit').classList.add('d-none');
    td.querySelector('.desc-view').classList.remove('d-none');
    return false;
  }

  const saveEditDescription = (btn) => {

    const td = qsClosest(btn, 'td');
    const view = td.querySelector('.desc-view');
    const edit = td.querySelector('.desc-edit');
    const input = edit.querySelector('input');
    const name = view.dataset.name;
    const newDesc = input.value;

    btn.disabled = true;

    updateTableDescriptionApiCall(name, newDesc, (result) => {
      view.querySelector('.desc-text').textContent = result.data.description || '';
      edit.classList.add('d-none');
      view.classList.remove('d-none');
      toaster.toastSuccess(result.message);
    });
    setTimeout(() => btn.disabled = false, 500);
    return false;
  }

  const elDatabasesAndTables = document.getElementById('databases-and-tables');
  elDatabasesAndTables.innerHTML = "<tr><td colspan='6'>{{ __('Loading...') }}</td></tr>";

  document.addEventListener('DOMContentLoaded', function () {
    listTablesApiCall(response => {
      if (!response.tables || response.tables.length === 0) {
        elDatabasesAndTables.innerHTML = "<tr><td colspan='6'>{{ __('No tables found.') }}</td></tr>";
      } else {
        const rows = response.tables.map(table => {

          const safeDesc = escapeHtml(table.description || '');
          const safeDescAttr = escapeAttr(table.description || '');

          return `
            <tr>
              <td>
                <span class="lozenge new">${table.name}</span>
              </td>
              <td class="text-end">${table.nb_rows}</td>
              <td class="text-end">${table.nb_columns}</td>
              <td>
                <div class="desc-view" data-name="${table.name}">
                  <span class="desc-text">${safeDesc}</span>
                  <a href="#" class="ms-2 text-decoration-none" onclick="startEditDescription(this)">
                    {{ __('Edit') }}
                  </a>
                </div>
                <div class="desc-edit d-none">
                  <div class="input-group input-group-sm">
                    <input type="text" class="form-control" value="${safeDescAttr}" maxlength="2000" />
                    <button class="btn btn-outline-primary" onclick="saveEditDescription(this)">
                      {{ __('Save') }}
                    </button>
                    <button class="btn btn-outline-secondary" onclick="cancelEditDescription(this)">
                      {{ __('Cancel') }}
                    </button>
                  </div>
                </div>
              </td>
              <td>${table.last_update}</td>
              <td>
                <span class="lozenge new">${table.status}</span>
              </td>
            </tr>
          `;
        });
        elDatabasesAndTables.innerHTML = rows.join('');
      }
    });
  });

</script>