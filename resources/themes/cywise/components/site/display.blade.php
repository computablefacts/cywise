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
    'xl' => ['font-size' => 'clamp(34px, 9vw, 72px)', 'stretch' => '125%'],
    'md' => ['font-size' => 'clamp(26px, 5.5vw, 50px)', 'stretch' => '118%'],
    default => ['font-size' => 'clamp(28px, 7vw, 60px)', 'stretch' => '112%'],
  };
@endphp

<{{ $as }} {{ $attributes->merge(['style' => "--ui-font-size: {$config['font-size']}; --ui-font-stretch: {$config['stretch']};", 'class' => "ui:m-0 ui:font-display ui:font-black ui:uppercase ui:tracking-[-0.035em] ui:text-balance ui:break-words ui:text-[length:var(--ui-font-size)] ui:font-stretch-[var(--ui-font-stretch)] ui:leading-none"]) }}>{{ $slot }}</{{ $as }}>
