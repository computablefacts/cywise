@extends('theme::iframes.app')

@section('content')
<div class="card mt-3 mb-3">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0" id="roles-permissions-table">
        <thead>
        <tr id="rnp-header">
          <th style="min-width:260px;">{{ __('Permission') }}</th>
          <!-- Roles will be injected here -->
        </tr>
        </thead>
        <tbody id="rnp-body">
        <!-- Rows will be injected here -->
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>

  const ALL_PERMISSIONS = @json($permissions ?? []);
  const CAN_EDIT = @json(request()->user()->isCywiseAdmin() ?? false);

  function buildTable(roles, permissions) {

    // Build header
    const theadRow = document.getElementById('rnp-header');

    // Remove previous role columns
    theadRow.querySelectorAll('th.role-col').forEach(el => el.remove());
    roles.forEach(r => {
      const th = document.createElement('th');
      th.className = 'role-col';
      th.textContent = r.name;
      theadRow.appendChild(th);
    });

    // Build body
    const tbody = document.getElementById('rnp-body');
    tbody.innerHTML = '';

    permissions.forEach(p => {

      const tr = document.createElement('tr');
      const tdPerm = document.createElement('td');
      tdPerm.textContent = p;
      tr.appendChild(tdPerm);

      roles.forEach(r => {
        const td = document.createElement('td');
        td.style.textAlign = 'center';
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.dataset.role = r.name; // use name for RPC simplicity
        input.dataset.permission = p;
        input.checked = r.permissions.includes(p);
        input.disabled = !CAN_EDIT;
        input.addEventListener('change', (evt) => {
          const checked = evt.target.checked;
          const role = evt.target.dataset.role;
          const perm = evt.target.dataset.permission;
          // optimistic UI already toggled; call backend
          const onFinally = () => { /* no-op */
          };
          if (checked) {
            addPermissionToRoleApiCall(role, perm, onFinally);
          } else {
            removePermissionFromRoleApiCall(role, perm, onFinally);
          }
        });
        td.appendChild(input);
        tr.appendChild(td);
      });

      tbody.appendChild(tr);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    listRolesAndPermissionsApiCall((result) => {
      const roles = result.roles || [];
      // Compute permissions set: include server-provided ALL_PERMISSIONS plus any found in roles
      const permsSet = new Set(ALL_PERMISSIONS);
      roles.forEach(r => (r.permissions || []).forEach(p => permsSet.add(p)));
      const permissions = Array.from(permsSet).sort((a, b) => a.localeCompare(b));
      buildTable(roles, permissions);
    });
  });
</script>
@endpush
