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
    'xl' => ['font-size' => 'clamp(34px, 7vw, 70px)', 'stretch' => '125%'],
    'md' => ['font-size' => 'clamp(24px, 3vw, 40px)', 'stretch' => '118%'],
    default => ['font-size' => 'clamp(28px, 4vw, 52px)', 'stretch' => '112%'],
  };
@endphp

<{{ $as }} {{ $attributes->merge(['class' => "ui:m-0 ui:font-display ui:font-black ui:uppercase ui:tracking-[-0.035em] ui:text-balance ui:break-words ui:leading-none", 'style' => "font-size: {$config['font-size']} !important; font-stretch: {$config['stretch']} !important;"]) }}>{{ $slot }}</{{ $as }}>
