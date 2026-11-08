<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('recipe');

?>

<x-dynamic-component component="layouts.app" :title="__('Remediation recipe')">
    <livewire:recipe-viewer :id="$id" />
</x-dynamic-component>

