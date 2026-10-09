@php
    $isEnglish = ($locale ?? 'fr') === 'en';
@endphp

{{-- One card per post; the parent provides the grid. --}}
@foreach ($posts as $post)
    @php
        $readingMinutes = app(\App\Services\BlogContent::class)->readingMinutes($post);
        $postUrl = $isEnglish
            ? route('blog.en.post', ['category' => $post->category, 'post' => $post])
            : $post->link();
    @endphp

    <a href="{{ $postUrl }}" class="ui:group ui:flex ui:flex-col ui:overflow-hidden ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:text-ink! ui:no-underline! ui:hover:border-slate-300">
        @if ($post->image())
            <img src="{{ $post->image() }}" alt="" class="ui:h-44 ui:w-full ui:object-cover">
        @else
            {{-- No image: ink block carrying the site strip --}}
            <span class="ui:flex ui:h-44 ui:flex-col ui:justify-end ui:bg-ink" aria-hidden="true"><x-site.strip/></span>
        @endif
        <span class="ui:flex ui:flex-1 ui:flex-col ui:gap-2 ui:p-5 ui:pb-6">
            <span class="ui:font-mono ui:text-xs ui:uppercase ui:text-brand-700">{{ $post->category->name }} / {{ $readingMinutes }} min</span>
            <span class="ui:text-[21px] ui:font-extrabold ui:leading-tight ui:tracking-tight ui:group-hover:text-brand-700">{{ $post->title }}</span>
            @if ($post->excerpt)
                <span class="ui:line-clamp-3 ui:text-[15px] ui:leading-relaxed ui:text-slate-600">{{ $post->excerpt }}</span>
            @endif
        </span>
    </a>
@endforeach
