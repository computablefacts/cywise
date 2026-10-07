{{--
  Data table. Styles its th / td descendants: rows stay plain HTML.

  <x-ui.table>
    <thead><tr><th>{{ __('Name') }}</th><th class="ui:text-right!">…</th></tr></thead>
    <tbody><tr><td>…</td></tr></tbody>
  </x-ui.table>

  Put it in a flush card: <x-ui.card flush>…</x-ui.card>
  Cell alignment needs "!" to beat the [&_th] / [&_td] rules above.
--}}
<div class="ui:overflow-x-auto">
  <table {{ $attributes->merge(['class' => 'ui:m-0 ui:w-full ui:border-collapse ui:text-sm ui:text-ink ui:[&_thead_tr]:bg-slate-50 ui:[&_th]:whitespace-nowrap ui:[&_th]:px-4 ui:[&_th]:py-2 ui:[&_th]:text-left ui:[&_th]:text-xs ui:[&_th]:font-medium ui:[&_th]:uppercase ui:[&_th]:tracking-wide ui:[&_th]:text-slate-500 ui:[&_td]:border-0 ui:[&_td]:border-t ui:[&_td]:border-solid ui:[&_td]:border-line ui:[&_td]:px-4 ui:[&_td]:py-3 ui:[&_td]:align-middle ui:[&_tbody_tr]:hover:bg-slate-50']) }}>
    {{ $slot }}
  </table>
</div>
