<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use function Laravel\Folio\{middleware, name};

middleware('auth');
name('recipe');

new class extends Component {

    public $id;

    #[Computed]
    public function recipe(): string
    {
        $alert = \App\Models\Alert::select('am_alerts.*')
                ->join('am_ports', 'am_ports.id', '=', 'am_alerts.port_id')
                ->join('am_scans', 'am_scans.id', '=', 'am_ports.scan_id')
                ->join('am_assets', 'am_assets.id', '=', 'am_scans.asset_id')
                ->join('users', 'users.id', '=', 'am_assets.created_by')
                ->where('am_alerts.id', $this->id)
                ->where('users.tenant_id', auth()->user()->tenant_id)
                ->first();
        return (new Parsedown)->text($alert?->ai_remediation ?? __('There is no remediation information available for this alert.'));
    }
}

?>

<x-dynamic-component component="layouts.app" :title="__('AI-generated remediation recipe')">
    @volt('recipe')
    <x-app.container>
        <x-card class="lg:p-10">
            <x-app.heading
                    title="{{ __('AI-generated remediation recipe') }}"
                    description=""
            />
            <div class="max-w-full mt-8 prose-sm prose dark:prose-invert">
                <div class="flex flex-col items-start space-y-3 lg:flex-row lg:space-y-0 lg:space-x-5">
                    <div class="relative">
                        <div class="mx-auto mt-5 prose-sm prose text-zinc-600 dark:text-zinc-300">
                            {!! $this->recipe !!}
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </x-app.container>
    @endvolt
</x-dynamic-component>

