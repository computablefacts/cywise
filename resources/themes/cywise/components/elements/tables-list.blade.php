<x-ui.table>
    <thead>
    <tr>
        <th>{{ __('Table') }}</th>
        <th class="ui:text-right!">{{ __('Number of Rows') }}</th>
        <th class="ui:text-right!">{{ __('Number of Columns') }}</th>
        <th>{{ __('Description') }}</th>
        <th>{{ __('Last Update') }}</th>
        <th>{{ __('Status') }}</th>
        <th>{{ __('Action') }}</th>
    </tr>
    </thead>
    <tbody id="databases-and-tables">
    <!-- FILLED DYNAMICALLY -->
    </tbody>
</x-ui.table>
<script>

    const escapeHtml = (str) => String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const escapeAttr = (str) => escapeHtml(str).replace(/`/g, '&#96;');
    const qsClosest = (el, selector) => el.closest(selector);
    const HIDDEN_CLASS = 'ui:hidden';

    const startEditDescription = (anchor) => {
        const td = qsClosest(anchor, 'td');
        const view = td.querySelector('.desc-view');
        const edit = td.querySelector('.desc-edit');
        const input = edit.querySelector('input');
        input.value = view.querySelector('.desc-text').textContent;
        view.classList.add(HIDDEN_CLASS);
        edit.classList.remove(HIDDEN_CLASS);
        input.focus();
        return false;
    }

    const cancelEditDescription = (btn) => {
        const td = qsClosest(btn, 'td');
        td.querySelector('.desc-edit').classList.add(HIDDEN_CLASS);
        td.querySelector('.desc-view').classList.remove(HIDDEN_CLASS);
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
            edit.classList.add(HIDDEN_CLASS);
            view.classList.remove(HIDDEN_CLASS);
            window.toaster.toastSuccess(result.message);
        });
        setTimeout(() => btn.disabled = false, 500);
        return false;
    }

    const forceTableImport = (tableId) => {
        if (confirm("{{ __('Are you sure you want to force the import of this table?') }}")) {
            forceTableImportApiCall(tableId);
        }
        return false;
    }

    const elDatabasesAndTables = document.getElementById('databases-and-tables');
    elDatabasesAndTables.innerHTML = "<tr><td colspan='7' class='ui:text-center ui:text-slate-500'>{{ __('Loading...') }}</td></tr>";

    document.addEventListener('DOMContentLoaded', function () {
        listTablesApiCall(response => {
            if (!response.tables || response.tables.length === 0) {
                elDatabasesAndTables.innerHTML = "<tr><td colspan='7' class='ui:text-center ui:text-slate-500'>{{ __('No tables found.') }}</td></tr>";
            } else {
                const rows = response.tables.map(table => {

                    const safeDesc = escapeHtml(table.description || '');
                    const safeDescAttr = escapeAttr(table.description || '');
                    const status = String(table.status || '');
                    const showForceImportButton = status.startsWith('Warning:');
                    const btnAction = showForceImportButton
                        ? `<button type="button" class="ui:h-8 ui:rounded-lg ui:border-0 ui:bg-critical ui:px-3 ui:text-xs ui:font-medium ui:text-white ui:cursor-pointer ui:hover:bg-red-700" onclick="return forceTableImport(${table.id})">{{ __('Force import') }}</button>`
                        : '';

                    return `
                        <tr>
                          <td>
                            <span class="ui:rounded-md ui:bg-slate-100 ui:px-2 ui:py-0.5 ui:font-mono ui:text-xs ui:text-slate-700">${table.name}</span>
                          </td>
                          <td class="ui:text-right! ui:tabular-nums">${table.nb_rows}</td>
                          <td class="ui:text-right! ui:tabular-nums">${table.nb_columns}</td>
                          <td>
                            <div class="desc-view ui:flex ui:items-center ui:gap-2" data-name="${table.name}">
                              <span class="desc-text ui:text-slate-600">${safeDesc}</span>
                              <a href="#" class="ui:text-xs ui:font-medium ui:text-brand-600! ui:no-underline! ui:hover:text-brand-700!" onclick="return startEditDescription(this)">
                                {{ __('Edit') }}
                              </a>
                            </div>
                            <div class="desc-edit ui:hidden">
                              <div class="ui:flex ui:items-center ui:gap-2">
                              <input type="text" value="${safeDescAttr}" maxlength="2000"
                                     class="ui:box-border ui:h-8 ui:min-w-0 ui:flex-1 ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:px-2 ui:text-sm ui:outline-none! ui:focus:border-brand-500 ui:focus:ring-2 ui:focus:ring-brand-100" />
                              <button type="button" class="ui:h-8 ui:rounded-lg ui:border-0 ui:bg-brand-500 ui:px-3 ui:text-xs ui:font-medium ui:text-white ui:cursor-pointer ui:hover:bg-brand-600" onclick="saveEditDescription(this)">
                                {{ __('Save') }}
                              </button>
                              <button type="button" class="ui:h-8 ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:px-3 ui:text-xs ui:font-medium ui:text-ink ui:cursor-pointer ui:hover:bg-slate-50" onclick="cancelEditDescription(this)">
                                {{ __('Cancel') }}
                              </button>
                              </div>
                            </div>
                          </td>
                          <td class="ui:whitespace-nowrap ui:text-slate-500">${table.last_update}</td>
                          <td>
                            <span class="ui:inline-flex ui:rounded-md ui:px-2 ui:py-0.5 ui:text-xs ui:font-medium ui:ring-1 ui:ring-inset ${showForceImportButton ? 'ui:bg-medium-soft ui:text-amber-700 ui:ring-amber-200' : 'ui:bg-slate-100 ui:text-slate-600 ui:ring-slate-200'}">${status}</span>
                          </td>
                          <td>${btnAction}</td>
                        </tr>
                      `;
                });
                elDatabasesAndTables.innerHTML = rows.join('');
            }
        });
    });

</script>