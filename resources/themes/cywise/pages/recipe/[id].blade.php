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
        $alert = \App\Models\Alert::find($this->id);
        return (new Parsedown)->text($alert?->ai_remediation ?? __('There is no remediation information available for this alert.'));
    }
}

?>

<x-dynamic-component :component="layouts.app" :title="__('AI-generated remediation recipe')">
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
</x-dynamic-component>

