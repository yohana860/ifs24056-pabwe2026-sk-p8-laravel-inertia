<?php

namespace App\Features\Post\Contracts;

use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    /** Artikel terbit untuk halaman publik, dengan pencarian judul/konten dan filter kategori. */
    public function paginatePublished(?string $search, ?string $category, int $perPage): LengthAwarePaginator;

    /** Artikel terbit terkait (kategori sama) selain artikel yang sedang dibaca. */
    public function relatedPublished(Post $post, int $limit): Collection;

    /** Artikel terbit berdasarkan slug; null bila tidak ada atau masih draf. */
    public function findPublishedBySlug(string $slug): ?Post;

    public function paginateForUser(int $userId, ?string $search, int $perPage): LengthAwarePaginator;

    /** @return array{total: int, published: int, draft: int} */
    public function statsForUser(int $userId): array;

    public function allForUser(int $userId): Collection;

    public function paginateAll(?string $search, int $perPage): LengthAwarePaginator;

    public function all(): Collection;

    /** @param array<string, mixed> $attributes */
    public function create(int $userId, array $attributes): Post;

    /** @param array<string, mixed> $attributes */
    public function update(Post $post, array $attributes): Post;

    public function delete(Post $post): void;

    public function slugExists(string $slug): bool;
}
