{{--
  Signage heading: Archivo black, expanded, uppercase.

  <x-site.display as="h1" size="xl">Votre entreprise est déjà <span class="ui:text-brand-500">une cible.</span></x-site.display>
--}}
@props([
  'as' => 'h2',
  'size' => 'lg', // xl (hero) | lg (section) | md (card, CTA)
])

@php
  $config = match ($size) {
    'xl' => ['font-size' => 'clamp(34px, 8vw, 100px)', 'stretch' => '125%'],
    'md' => ['font-size' => 'clamp(26px, 3.5vw, 48px)', 'stretch' => '118%'],
    default => ['font-size' => 'clamp(28px, 4.5vw, 68px)', 'stretch' => '112%'],
  };
@endphp

<{{ $as }} {{ $attributes->merge(['class' => "ui:m-0 ui:font-display ui:font-black ui:uppercase ui:tracking-[-0.035em] ui:text-balance ui:break-words ui:leading-none", 'style' => "font-size: {$config['font-size']} !important; font-stretch: {$config['stretch']} !important;"]) }}>{{ $slot }}</{{ $as }}>
