<?php
    use function Laravel\Folio\{name};
    name('blog.category');
?>

<x-layouts.marketing>

    @php
        $posts = $category->posts()->orderBy('created_at', 'DESC')->paginate(6);
    @endphp

    <x-container>
        <div class="mx-auto max-w-2xl px-6 lg:max-w-7xl lg:px-8 relative pt-12">
            <x-marketing.elements.heading
                title="{{ $category->name }} Articles"
                description="{{ __('Our latest :category posts below.', [ 'category' => $category->name ]) }}"
                align="left"
            />
            
            @include('theme::partials.blog.categories')

            <div class="grid gap-5 mx-auto mt-5 md:mt-10 sm:grid-cols-2 lg:grid-cols-3">
                @include('theme::partials.blog.posts-loop', ['posts' => $posts])
            </div>
        </div>

        <div class="flex justify-center my-10">
            {{ $posts->links('theme::partials.pagination') }}
        </div>

    </x-container>
</x-layouts.marketing>
