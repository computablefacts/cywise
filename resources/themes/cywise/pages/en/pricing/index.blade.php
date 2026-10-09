<?php

use function Laravel\Folio\{name};

name('website.en.pricing');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('pricing')"
    :seo="[
        'title' => 'Pricing — Cywise',
        'description' => 'Simple Cywise pricing concept for growing teams.',
    ]"
>
<main>
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-14 ui:pt-16 ui:sm:px-6">
        <x-site.display as="h1" size="xl" class="ui:max-w-[1100px] ui:text-[clamp(30px,4.2vw,54px)]!">Security without enterprise complexity.</x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[560px] ui:text-xl ui:leading-normal ui:text-slate-700">Start with clear visibility. Add expert testing when you need it.</p>
    </section>

    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:sm:px-6">
        <div class="ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-4">
            @include('theme::partials.website-v2.plans', ['locale' => 'en'])
        </div>
    </section>
</main>
</x-layouts.website-v2>
