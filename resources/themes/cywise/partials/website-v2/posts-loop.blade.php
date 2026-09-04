@php
    $isEnglish = ($locale ?? 'fr') === 'en';
    $visuals = [
        ['class' => 'visual-grid', 'label' => '01'],
        ['class' => 'visual-terminal', 'label' => '>_'],
        ['class' => 'visual-lock', 'label' => '×'],
    ];
@endphp

@foreach ($posts as $post)
    @php
        $visual = $visuals[$loop->index % count($visuals)];
        $readingMinutes = app(\App\Services\BlogContent::class)->readingMinutes($post);
        $postUrl = $isEnglish
            ? route('blog.en.post', ['category' => $post->category, 'post' => $post])
            : $post->link();
    @endphp

    <div class="col-lg-4">
        <article class="article-card">
            <div class="article-visual {{ $visual['class'] }}">{{ $visual['label'] }}</div>
            <span class="mono">{{ $post->category->name }} / {{ $readingMinutes }} MIN</span>
            <h3>{{ $post->title }}</h3>

            @if ($post->excerpt)
                <p>{{ $post->excerpt }}</p>
            @endif

            <a href="{{ $postUrl }}">{{ $isEnglish ? 'READ →' : 'LIRE →' }}</a>
        </article>
    </div>
@endforeach
