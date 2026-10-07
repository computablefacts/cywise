<?php
    use function Laravel\Folio\{middleware, name};
    middleware('auth');
    name('settings.invoices');
?>

@php
    $invoices = auth()->user()->invoices();
@endphp

<x-layouts.app>
        <div class="">
            <x-app.settings-layout
                :title="__('Invoices')"
                :description="__('Your past plan invoices')"
            >
                <x-ui.card flush>
                    @empty($invoices)
                        <x-ui.empty icon="invoice">{{ __("You do not have any past invoices. When you subscribe to a plan you'll see your past invoices here.") }}</x-ui.empty>
                    @else
                        <x-ui.table>
                            <thead>
                                <tr>
                                    <th>{{ __('Price') }}</th>
                                    <th>{{ __('Date of Invoice') }}</th>
                                    <th class="ui:text-right">{{ __('PDF Download') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr wire:key="invoice-{{ $invoice->id }}">
                                        <td class="ui:font-medium ui:tabular-nums">€{{ $invoice->total }}</td>
                                        <td class="ui:tabular-nums">{{ $invoice->created }}</td>
                                        <td class="ui:text-right">
                                            <x-ui.button variant="ghost" size="sm" icon="download-simple" :href="$invoice->download" :target="config('wave.billing_provider') == 'stripe' ? '_blank' : null">{{ __('Download') }}</x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @endempty
                </x-ui.card>
            </x-app.settings-layout>
        </div>
</x-layouts.app>
