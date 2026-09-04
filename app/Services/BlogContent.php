<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Wave\Category;
use Wave\Post;

class BlogContent
{
    private const PUBLISHED_STATUS = 'PUBLISHED';

    private const PAGE_SIZE = 6;

    private const HOME_POST_LIMIT = 3;

    private const WORDS_PER_MINUTE = 200;

    // Keep publication rules out of Blade and identical on every page.
    public function homePosts(): Collection
    {
        return Post::query()
            ->where('status', self::PUBLISHED_STATUS)
            ->whereHas('category')
            ->with('category')
            ->latest()
            ->limit(self::HOME_POST_LIMIT)
            ->get();
    }

    public function posts(?Category $category = null): LengthAwarePaginator
    {
        $query = $category === null
            ? Post::query()->whereHas('category')
            : $category->posts();

        return $query
            ->where('status', self::PUBLISHED_STATUS)
            ->with('category')
            ->latest()
            ->paginate(self::PAGE_SIZE);
    }

    public function categories(): Collection
    {
        return Category::query()
            ->whereHas('posts', fn ($query) => $query
                ->where('status', self::PUBLISHED_STATUS))
            ->orderBy('name')
            ->get();
    }

    public function assertPost(Post $post, Category $category): void
    {
        abort_unless($post->status === self::PUBLISHED_STATUS, 404);
        abort_unless($post->category_id === $category->id, 404);
    }

    public function readingMinutes(Post $post): int
    {
        $text = trim(strip_tags($post->body));
        $words = $text === '' ? [] : (preg_split('/\s+/u', $text) ?: []);

        return max(1, (int) ceil(count($words) / self::WORDS_PER_MINUTE));
    }
}
