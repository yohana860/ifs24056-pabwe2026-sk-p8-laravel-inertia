<?php

namespace Tests\Unit\Post;

use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\FakeImage;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = $this->app->make(PostService::class);
    }

    private function data(array $override = []): array
    {
        return array_merge([
            'title' => 'Judul Artikel',
            'category' => 'Umum',
            'excerpt' => null,
            'content' => 'Isi',
            'is_published' => false,
        ], $override);
    }

    public function test_create_draft_has_no_published_at(): void
    {
        $post = $this->service->create(User::factory()->create(), $this->data());

        $this->assertSame('judul-artikel', $post->slug);
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
    }

    public function test_create_published_sets_published_at(): void
    {
        $post = $this->service->create(User::factory()->create(), $this->data(['is_published' => true]));

        $this->assertNotNull($post->published_at);
    }

    public function test_slug_is_unique_and_has_fallback(): void
    {
        $user = User::factory()->create();

        $a = $this->service->create($user, $this->data());
        $b = $this->service->create($user, $this->data());
        $c = $this->service->create($user, $this->data());
        $d = $this->service->create($user, $this->data(['title' => '!!!']));

        $this->assertSame('judul-artikel', $a->slug);
        $this->assertSame('judul-artikel-2', $b->slug);
        $this->assertSame('judul-artikel-3', $c->slug);
        $this->assertSame('artikel', $d->slug);
    }

    public function test_update_keeps_slug_and_manages_published_at(): void
    {
        $post = $this->service->create(User::factory()->create(), $this->data(['is_published' => true]));
        $originalPublishedAt = $post->published_at;

        $updated = $this->service->update($post, $this->data(['title' => 'Judul Baru', 'is_published' => true]));
        $this->assertSame('judul-artikel', $updated->slug);
        $this->assertSame('Judul Baru', $updated->title);
        $this->assertTrue($updated->published_at->equalTo($originalPublishedAt));

        $draft = $this->service->update($post, $this->data());
        $this->assertNull($draft->published_at);

        $republished = $this->service->update($post, $this->data(['is_published' => true]));
        $this->assertNotNull($republished->published_at);
    }

    public function test_create_and_update_with_cover_replace_the_old_file(): void
    {
        $post = $this->service->create(User::factory()->create(), $this->data(), FakeImage::make('a.jpg'));
        $first = $post->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $post = $this->service->update($post, $this->data(), FakeImage::make('b.jpg'));

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($post->cover_image_path);
    }

    public function test_remove_cover_with_and_without_existing_file(): void
    {
        $post = $this->service->create(User::factory()->create(), $this->data(), FakeImage::make('a.jpg'));
        $path = $post->cover_image_path;

        $post = $this->service->removeCover($post);
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($post->cover_image_path);

        $this->assertNull($this->service->removeCover($post)->cover_image_path);
    }

    public function test_delete_removes_cover_file(): void
    {
        $post = $this->service->create(User::factory()->create(), $this->data(), FakeImage::make('a.jpg'));
        $path = $post->cover_image_path;

        $this->service->delete($post);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseCount('posts', 0);

        $plain = $this->service->create(User::factory()->create(), $this->data());
        $this->service->delete($plain);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_toggle_publish_both_ways(): void
    {
        $post = $this->service->create(User::factory()->create(), $this->data());

        $post = $this->service->togglePublish($post);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);

        $post = $this->service->togglePublish($post);
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
    }

    public function test_create_many_counts_items(): void
    {
        $user = User::factory()->create();

        $count = $this->service->createMany($user, [
            $this->data(['title' => 'Satu']),
            $this->data(['title' => 'Dua', 'is_published' => true]),
        ]);

        $this->assertSame(2, $count);
        $this->assertSame(2, $user->posts()->count());
    }

    public function test_find_published_or_fail(): void
    {
        Post::factory()->create(['slug' => 'ada']);
        Post::factory()->draft()->create(['slug' => 'draf']);

        $this->assertSame('ada', $this->service->findPublishedOrFail('ada')->slug);

        $this->expectException(NotFoundHttpException::class);
        $this->service->findPublishedOrFail('draf');
    }

    public function test_read_helpers_delegate_to_repository(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create(['category' => 'Opini']);
        Post::factory()->create(['category' => 'Opini']);

        $this->assertSame(2, $this->service->paginatePublic(null, null)->total());
        $this->assertCount(1, $this->service->related($post));
        $this->assertSame(1, $this->service->paginateForUser($user, null)->total());
        $this->assertSame(1, $this->service->statsForUser($user)['total']);
        $this->assertCount(1, $this->service->allForUser($user));
        $this->assertSame(2, $this->service->paginateAll(null)->total());
        $this->assertCount(2, $this->service->all());
    }
}
