<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(private readonly PostRepositoryInterface $posts) {}

    public function paginatePublic(?string $search, ?string $category, int $perPage = 9): LengthAwarePaginator
    {
        return $this->posts->paginatePublished($search, $category, $perPage);
    }

    /** Artikel terbit berdasarkan slug, atau 404 jika tidak ada / masih draf. */
    public function findPublishedOrFail(string $slug): Post
    {
        return $this->posts->findPublishedBySlug($slug) ?? abort(404);
    }

    public function related(Post $post, int $limit = 3): Collection
    {
        return $this->posts->relatedPublished($post, $limit);
    }

    public function paginateForUser(User $user, ?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return $this->posts->paginateForUser($user->id, $search, $perPage);
    }

    /** @return array{total: int, published: int, draft: int} */
    public function statsForUser(User $user): array
    {
        return $this->posts->statsForUser($user->id);
    }

    public function allForUser(User $user): Collection
    {
        return $this->posts->allForUser($user->id);
    }

    public function paginateAll(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->posts->paginateAll($search, $perPage);
    }

    public function all(): Collection
    {
        return $this->posts->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, ?UploadedFile $cover = null): Post
    {
        $payload = $this->preparePayload($data);
        $payload['slug'] = $this->uniqueSlug($payload['title']);

        $post = $this->posts->create($user->id, $payload);

        if ($cover !== null) {
            $post = $this->storeCover($post, $cover);
        }

        return $post;
    }

    /**
     * Buat banyak artikel sekaligus (impor Excel / simpan batch AI).
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function createMany(User $user, array $items): int
    {
        foreach ($items as $item) {
            $this->create($user, $item);
        }

        return count($items);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Post $post, array $data, ?UploadedFile $cover = null): Post
    {
        $post = $this->posts->update($post, $this->preparePayload($data, $post));

        if ($cover !== null) {
            $post = $this->storeCover($post, $cover);
        }

        return $post;
    }

    public function delete(Post $post): void
    {
        $this->deleteCoverFile($post);
        $this->posts->delete($post);
    }

    /** Toggle Draft <-> Published dan jaga konsistensi published_at. */
    public function togglePublish(Post $post): Post
    {
        $publish = ! $post->is_published;

        return $this->posts->update($post, [
            'is_published' => $publish,
            'published_at' => $publish ? now() : null,
        ]);
    }

    public function storeCover(Post $post, UploadedFile $file): Post
    {
        $this->deleteCoverFile($post);

        return $this->posts->update($post, [
            'cover_image_path' => $file->store('covers', 'public'),
        ]);
    }

    public function removeCover(Post $post): Post
    {
        $this->deleteCoverFile($post);

        return $this->posts->update($post, ['cover_image_path' => null]);
    }

    public function uniqueSlug(string $title): string
    {
        $base = mb_substr(Str::slug($title), 0, 200) ?: 'artikel';
        $slug = $base;
        $suffix = 2;

        while ($this->posts->slugExists($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data, ?Post $existing = null): array
    {
        $published = (bool) ($data['is_published'] ?? false);

        return [
            'title' => $data['title'],
            'category' => $data['category'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'is_published' => $published,
            'published_at' => $published ? ($existing?->published_at ?? now()) : null,
        ];
    }

    private function deleteCoverFile(Post $post): void
    {
        if ($post->cover_image_path) {
            Storage::disk('public')->delete($post->cover_image_path);
        }
    }
}
