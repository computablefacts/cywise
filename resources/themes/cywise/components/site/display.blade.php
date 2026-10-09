{{--
  Signage heading: Archivo black, expanded, uppercase.

  <x-site.display as="h1" size="xl">Votre entreprise est déjà <span class="ui:text-brand-500">une cible.</span></x-site.display>
--}}
@props([
  'as' => 'h2',
  'size' => 'lg', // xl (hero) | lg (section) | md (card, CTA)
])

@php
  $sizes = match ($size) {
    'xl' => 'ui:text-[clamp(34px,9vw,148px)] ui:leading-none ui:font-stretch-125%',
    'md' => 'ui:text-[clamp(28px,3.8vw,56px)] ui:leading-[1.02] ui:font-stretch-118%',
    default => 'ui:text-[clamp(28px,5vw,76px)] ui:leading-[1.02] ui:font-stretch-112%',
  };
@endphp

<{{ $as }} {{ $attributes->merge(['class' => "ui:m-0 ui:font-display ui:font-black ui:uppercase ui:tracking-[-0.035em] ui:text-balance ui:break-words $sizes"]) }}>{{ $slot }}</{{ $as }}>
