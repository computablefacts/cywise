<?php

use function Laravel\Folio\{name};

name('terms');

$file = file_exists(public_path('/cywise/markdown/terms.' . app()->getLocale() . '.md'))
    ? public_path('/cywise/markdown/terms.' . app()->getLocale() . '.md')
    : public_path('/cywise/markdown/terms.md');

$html = (new Parsedown)->text(file_get_contents($file));

?>

<x-layouts.website-v2
    :locale="app()->getLocale()"
    :seo="[
        'title' => __('Terms and Conditions') . ' — Cywise',
        'description' => '',
    ]"
>
<main class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:pt-16 ui:sm:px-6">
    <x-site.display as="h1" size="xl" class="ui:mb-12">{{ __('Terms and Conditions') }}</x-site.display>
    <div class="ui:prose ui:prose-slate ui:max-w-none">
        {!! $html !!}
    </div>
</main>
</x-layouts.website-v2>

