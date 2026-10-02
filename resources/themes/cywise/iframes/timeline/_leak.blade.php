{{-- Leak rows (one batch of the same leak date). The page wraps them in a single table. --}}
@foreach($leaks as $l)
<tr data-search class="ui:border-0 ui:border-t ui:border-solid ui:border-line">
  <td class="ui:whitespace-nowrap ui:px-4 ui:py-3 ui:text-slate-500">{{ empty($l->leak_date) ? '-' : $l->leak_date }}</td>
  <td class="ui:px-4 ui:py-3 ui:font-medium ui:text-ink">{{ $l->email }}</td>
  <td class="ui:max-w-56 ui:truncate ui:px-4 ui:py-3 ui:text-slate-600" title="{{ $l->website }}">{{ empty($l->website) ? '-' : $l->website }}</td>
  <td class="ui:px-4 ui:py-3 ui:font-mono ui:text-xs ui:text-slate-600">{{ empty($l->password) ? '-' : $l->password }}</td>
  <td class="ui:px-4 ui:py-3">
    <x-ui.badge>{{ $l->leak_type ?? (empty($l->website) ? __('leak') : __('infostealer log')) }}</x-ui.badge>
  </td>
</tr>
@endforeach
