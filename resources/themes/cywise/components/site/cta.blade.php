{{--
  Closing call to action band.

  <x-site.cta :href="route('register')" label="Commencer →">
    <x-slot:title>Rendez votre<br>sécurité visible.</x-slot:title>
  </x-site.cta>
  tone="dark": ink background (blog post).
--}}
@props([
  'title',          // slot: HTML allowed (line breaks, accent span)
  'href',
  'label',
  'tone' => 'brand', // brand | dark
])

<section class="ui:px-4 ui:pb-24 ui:sm:px-6">
  <div class="ui:mx-auto ui:flex ui:max-w-[1192px] ui:flex-wrap ui:items-end ui:justify-between ui:gap-8 ui:rounded-3xl ui:p-8 ui:sm:p-14 {{ $tone === 'dark' ? 'ui:bg-ink' : 'ui:bg-brand-500' }} ui:text-white">
    <x-site.display size="md" class="ui:max-w-3xl">{!! $title !!}</x-site.display>
    <x-site.button :href="$href" :variant="$tone === 'dark' ? 'primary' : 'dark'">{{ $label }}</x-site.button>
  </div>
</section>
