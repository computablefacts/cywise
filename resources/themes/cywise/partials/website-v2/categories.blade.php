@php
    $isEnglish = ($locale ?? 'fr') === 'en';
@endphp

<nav aria-label="{{ $isEnglish ? 'Blog categories' : 'Catégories du blog' }}" class="blog-categories">
    <a class="{{ isset($category) ? '' : 'active' }}" href="{{ route($isEnglish ? 'blog.en' : 'blog') }}">
        {{ $isEnglish ? 'ALL' : 'TOUT' }}
    </a>

    @foreach ($categories as $item)
        <a
            class="{{ isset($category) && $category->is($item) ? 'active' : '' }}"
            href="{{ route($isEnglish ? 'blog.en.category' : 'blog.category', ['category' => $item]) }}"
        >
            {{ $item->name }}
        </a>
    @endforeach
</nav>
