<?php
    use function Laravel\Folio\{name};
    name('blog.post');
?>

<x-layouts.marketing
    :seo="[
        'title' => $post->title . ' | Cywise',
        'description' => $post->excerpt ?? '',
    ]"
>
    <article id="post-{{ $post->id }}" class="max-w-4xl px-5 pb-20 mx-auto prose prose-md dark:prose-invert lg:prose-lg lg:px-0">

        <x-elements.back-button
            class="max-w-4xl mx-auto mt-4 md:mt-8"
            text="{{ __('back to the blog') }}"
            :href="route('blog')"
        />

        <meta property="name" content="{{ $post->title }}">
        <meta property="author" typeof="Person" content="{{ $post->user->name }}">
        <meta property="dateModified" content="{{ Carbon\Carbon::parse($post->updated_at)->toIso8601String() }}">
        <meta class="uk-margin-remove-adjacent" property="datePublished" content="{{ Carbon\Carbon::parse($post->created_at)->toIso8601String() }}">

        <div class="max-w-4xl mx-auto mt-6">
            <h1 class="flex flex-col leading-none">
                <span>{{ $post->title }}</span>
                <span class="mt-0 mt-10 text-base font-normal">
                  Posté dans la catégorie <a href="{{ $post->linkCategory() }}" class="relative z-10 px-3 py-1.5 font-medium text-blue-600 bg-blue-50 rounded-full hover:bg-blue-100" style="text-decoration:none;" rel="category">{{ $post->category->name }}</a> le <time datetime="{{ Carbon\Carbon::parse($post->created_at)->toIso8601String() }}">{{ Carbon\Carbon::parse($post->created_at)->toFormattedDateString() }}</time> par {{ $post->user->name }}.
                </span>
            </h1>
        </div>
<!--
        @if(!empty($post->image()))
        <div class="relative">
            <img class="w-full h-auto rounded-lg" src="{{ $post->image() }}" alt="{{ $post->title }}" srcset="{{ $post->image() }}">
        </div>
        @endif
-->
        <div class="max-w-4xl mx-auto">
            {!! $post->body !!}
        </div>
    </article>
</x-layouts.marketing>
