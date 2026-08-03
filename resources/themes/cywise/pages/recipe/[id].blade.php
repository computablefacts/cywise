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
        <div class="card mt-3">
            <div class="card-body p-4">
            <x-app.heading
                    title="{!! __('AI-generated remediation recipe') !!}"
                    description=""
            />
            <div class="mt-3">
                <div class="d-flex flex-column align-items-start gap-3 flex-lg-row gap-lg-4">
                    <div>
                        <div class="mt-2 text-muted">
                            {!! $this->recipe !!}
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-dynamic-component>

