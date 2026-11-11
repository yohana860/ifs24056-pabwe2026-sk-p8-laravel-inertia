<?php

namespace App\Features\Post\Repositories;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PostRepository implements PostRepositoryInterface
{
    /**
     * Kolom untuk daftar publik (tanpa konten lengkap agar payload ringan).
     *
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id', 'user_id', 'title', 'slug', 'category', 'excerpt',
        'cover_image_path', 'is_published', 'published_at', 'created_at', 'updated_at',
    ];

    public function paginatePublished(?string $search, ?string $category, int $perPage): LengthAwarePaginator
    {
        $query = Post::query()
            ->select(self::LIST_COLUMNS)
            ->with('user:id,name,username')
            ->where('is_published', true)
            ->when($category !== null && $category !== '', fn (Builder $q) => $q->where('category', $category));

        return $this->applySearch($query, $search)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function relatedPublished(Post $post, int $limit): Collection
    {
        return Post::query()
            ->select(self::LIST_COLUMNS)
            ->with('user:id,name,username')
            ->where('is_published', true)
            ->where('category', $post->category)
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function findPublishedBySlug(string $slug): ?Post
    {
        return Post::query()
            ->with('user:id,name,username')
            ->where('is_published', true)
            ->where('slug', $slug)
            ->first();
    }

    public function paginateForUser(int $userId, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->applySearch(Post::query()->where('user_id', $userId), $search)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function statsForUser(int $userId): array
    {
        $base = Post::query()->where('user_id', $userId);

        return [
            'total' => (clone $base)->count(),
            'published' => (clone $base)->where('is_published', true)->count(),
            'draft' => (clone $base)->where('is_published', false)->count(),
        ];
    }

    public function allForUser(int $userId): Collection
    {
        return Post::query()
            ->with('user:id,name,username')
            ->where('user_id', $userId)
            ->orderBy('id')
            ->get();
    }

    public function paginateAll(?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->applySearch(Post::query()->with('user:id,name,username'), $search)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function all(): Collection
    {
        return Post::query()->with('user:id,name,username')->orderBy('id')->get();
    }

    public function create(int $userId, array $attributes): Post
    {
        $post = new Post($attributes);
        $post->user_id = $userId;
        $post->save();

        return $post;
    }

    public function update(Post $post, array $attributes): Post
    {
        $post->update($attributes);

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    public function slugExists(string $slug): bool
    {
        return Post::query()->where('slug', $slug)->exists();
    }

    /**
     * Pencarian portabel (PostgreSQL & SQLite) pada judul dan konten.
     */
    private function applySearch(Builder $query, ?string $search): Builder
    {
        $term = trim((string) $search);

        return $query->when($term !== '', function (Builder $q) use ($term) {
            $like = '%'.mb_strtolower($term).'%';

            $q->where(function (Builder $w) use ($like) {
                $w->whereRaw('LOWER(posts.title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(posts.content) LIKE ?', [$like]);
            });
        });
    }
}
