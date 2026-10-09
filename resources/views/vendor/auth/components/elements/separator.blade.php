<div {{ $attributes->merge(['class' => 'ui:flex ui:w-full ui:items-center ui:gap-3 ui:font-mono ui:text-xs ui:uppercase ui:text-slate-400']) }}>
    <span class="ui:h-px ui:flex-1 ui:bg-line"></span>
    {{ __(trim($slot->toHtml())) }}
    <span class="ui:h-px ui:flex-1 ui:bg-line"></span>
</div>
