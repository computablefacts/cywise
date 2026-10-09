@php
    $isEnglish = ($locale ?? 'fr') === 'en';

    // Filter chips, 44px tall: active = ink filled, others bordered
    $chip = 'ui:inline-flex ui:h-11 ui:items-center ui:rounded-full ui:border ui:border-solid ui:px-5 ui:font-mono ui:text-[13px] ui:uppercase ui:no-underline! ui:transition-colors';
    $active = 'ui:border-ink ui:bg-ink ui:text-white!';
    $idle = 'ui:border-line ui:bg-white ui:text-ink! ui:hover:border-ink';
@endphp

<nav aria-label="{{ $isEnglish ? 'Blog categories' : 'Catégories du blog' }}" class="ui:flex ui:flex-wrap ui:gap-2">
    <a
        class="{{ $chip }} {{ isset($category) ? $idle : $active }}"
        href="{{ route($isEnglish ? 'blog.en' : 'blog') }}"
        @if (!isset($category)) aria-current="page" @endif
    >
        {{ $isEnglish ? 'All' : 'Tout' }}
    </a>

    @foreach ($categories as $item)
        @php
            $isActive = isset($category) && $category->is($item);
        @endphp

        <a
            class="{{ $chip }} {{ $isActive ? $active : $idle }}"
            href="{{ route($isEnglish ? 'blog.en.category' : 'blog.category', ['category' => $item]) }}"
            @if ($isActive) aria-current="page" @endif
        >
            {{ $item->name }}
        </a>
    @endforeach
</nav>
