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
                title="Invoices"
                description="Your past plan invoices"
            >
                @empty($invoices)
                    <x-app.alert id="dashboard_alert">No invoices available.</x-app.alert>
                    <p class="mt-3">You do not have any past invoices. When you subscribe to a plan you'll see your past invoices here.</p>
                @else
                    <div class="table-responsive border rounded">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small fw-bold text-uppercase text-muted">Price</th>
                                    <th class="small fw-bold text-uppercase text-muted">Date of Invoice</th>
                                    <th class="small fw-bold text-uppercase text-muted text-end">PDF Download</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr wire:key="invoice-{{ $invoice->id }}">
                                        <td class="small fw-medium">€{{ $invoice->total }}</td>
                                        <td class="small fw-medium">{{ $invoice->created }}</td>
                                        <td class="small text-end">
                                            <a href="{{ $invoice->download }}" @if(config("wave.billing_provider") == 'stripe') target="_blank" @endif class="text-primary text-decoration-none fw-medium">Download</a>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endempty

            </x-app.settings-layout>
        </div>
</x-layouts.app>
