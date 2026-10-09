{{-- Page title. The logo lives in layouts/app; texts come from config('devdojo.auth.language') and are translated here. --}}
@props([
    'align' => 'center',
    'text' => 'Heading Text',
    'description' => '',
    'show_subheadline' => false
])

<div id="auth-heading-container" class="ui:mb-8">
    <x-site.display as="h1" size="md" id="auth-heading-title" class="ui:text-[clamp(30px,3vw,40px)]!">{{ __($text ?? '') }}</x-site.display>
    @if(($description ?? false) && $show_subheadline)
        <p id="auth-heading-description" class="ui:m-0 ui:mt-3 ui:text-[15px] ui:leading-normal ui:text-slate-600">{{ __($description ?? '') }}</p>
    @endif
</div>
