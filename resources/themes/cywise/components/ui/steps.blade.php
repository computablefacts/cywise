{{--
  Numbered steps of a setup guide, linked by a vertical line.

    (1) Create a bot
     │  text…
    (2) Save the token
        [field] [Save]

  <x-ui.steps :steps="[__('Create a bot'), __('Save the token')]">
    <x-slot:step1>…</x-slot:step1>
    <x-slot:step2>…</x-slot:step2>
  </x-ui.steps>
--}}
@props([
  'steps', // step titles, in order; the content of step N is the slot "stepN"
])

<ol class="ui:m-0 ui:flex ui:list-none ui:flex-col ui:p-0">
  @foreach($steps as $i => $title)
    @php
      $number = $i + 1;
      $content = ${"step{$number}"} ?? null;
    @endphp
    <li class="ui:relative ui:flex ui:gap-3 ui:pb-6 ui:last:pb-0">
      @if(!$loop->last)
        <span class="ui:absolute ui:bottom-0 ui:left-3.5 ui:top-8 ui:w-px ui:bg-line" aria-hidden="true"></span>
      @endif
      <span class="ui:flex ui:size-7 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-full ui:bg-brand-50 ui:text-xs ui:font-semibold ui:text-brand-700 ui:ring-1 ui:ring-brand-200">
        {{ $number }}
      </span>
      <div class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-2 ui:pt-1">
        <span class="ui:text-sm ui:font-semibold ui:text-ink">{{ $title }}</span>
        @if($content)
          <div class="ui:flex ui:flex-col ui:gap-2 ui:text-sm ui:text-slate-600">{{ $content }}</div>
        @endif
      </div>
    </li>
  @endforeach
</ol>
