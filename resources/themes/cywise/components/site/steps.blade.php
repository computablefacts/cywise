{{--
  "How it works": big signage title next to numbered steps.

  <x-site.steps :steps="[['title' => …, 'text' => …], …]">
    <x-slot:title>Voyez.<br>Priorisez.<br><span class="ui:text-brand-500">Agissez.</span></x-slot:title>
  </x-site.steps>
--}}
@props([
  'title', // slot: HTML allowed (line breaks, accent span)
  'steps',
])

<section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:py-24 ui:sm:px-6">
  <div class="ui:grid ui:grid-cols-1 ui:gap-12 ui:lg:grid-cols-2">
    <x-site.display size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">{!! $title !!}</x-site.display>
    <ol class="ui:m-0 ui:list-none ui:self-end ui:p-0">
      @foreach($steps as $step)
        <li class="ui:flex ui:gap-5 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:py-6">
          <span class="ui:flex ui:size-11 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-xl ui:bg-ink ui:font-mono ui:text-sm ui:text-white">{{ sprintf('%02d', $loop->iteration) }}</span>
          <span>
            <span class="ui:block ui:font-display ui:text-[22px] ui:font-extrabold ui:tracking-tight">{{ $step['title'] }}</span>
            <span class="ui:mt-1.5 ui:block ui:text-base ui:leading-relaxed ui:text-slate-600">{{ $step['text'] }}</span>
          </span>
        </li>
      @endforeach
    </ol>
  </div>
</section>
