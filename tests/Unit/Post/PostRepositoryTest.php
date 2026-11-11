<?php

namespace Tests\Unit\Post;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PostRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new PostRepository;
    }

    public function test_paginate_published_excludes_drafts_and_filters(): void
    {
        Post::factory()->create(['title' => 'Belajar PHP', 'content' => 'isi biasa', 'category' => 'Teknologi']);
        Post::factory()->create(['title' => 'Kopi pagi', 'content' => 'resep SEDUH', 'category' => 'Gaya Hidup']);
        Post::factory()->draft()->create(['title' => 'Draf rahasia', 'category' => 'Teknologi']);

        $this->assertSame(2, $this->repo->paginatePublished(null, null, 10)->total());
        $this->assertSame(1, $this->repo->paginatePublished('php', null, 10)->total());
        $this->assertSame(1, $this->repo->paginatePublished('seduh', null, 10)->total());
        $this->assertSame(1, $this->repo->paginatePublished(null, 'Gaya Hidup', 10)->total());
        $this->assertSame(0, $this->repo->paginatePublished('draf', null, 10)->total());
        $this->assertSame(2, $this->repo->paginatePublished('  ', '', 10)->total());
    }

    public function test_published_list_does_not_load_full_content(): void
    {
        Post::factory()->create();

        $post = $this->repo->paginatePublished(null, null, 10)->items()[0];

        $this->assertArrayNotHasKey('content', $post->getAttributes());
        $this->assertTrue($post->relationLoaded('user'));
    }

    public function test_find_published_by_slug(): void
    {
        Post::factory()->create(['slug' => 'terbit']);
        Post::factory()->draft()->create(['slug' => 'draf']);

        $this->assertNotNull($this->repo->findPublishedBySlug('terbit'));
        $this->assertNull($this->repo->findPublishedBySlug('draf'));
        $this->assertNull($this->repo->findPublishedBySlug('tidak-ada'));
    }

    public function test_related_published_same_category_excluding_self_and_drafts(): void
    {
        $main = Post::factory()->create(['category' => 'Opini']);
        $related = Post::factory()->create(['category' => 'Opini']);
        Post::factory()->create(['category' => 'Bisnis']);
        Post::factory()->draft()->create(['category' => 'Opini']);

        $result = $this->repo->relatedPublished($main, 3);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($related));
    }

    public function test_user_scoped_queries_and_stats(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->count(2)->create();
        Post::factory()->for($user)->draft()->create(['title' => 'Cari saya']);
        Post::factory()->create();

        $this->assertSame(3, $this->repo->paginateForUser($user->id, null, 10)->total());
        $this->assertSame(1, $this->repo->paginateForUser($user->id, 'cari', 10)->total());
        $this->assertCount(3, $this->repo->allForUser($user->id));
        $this->assertSame(['total' => 3, 'published' => 2, 'draft' => 1], $this->repo->statsForUser($user->id));
    }

    public function test_admin_queries(): void
    {
        Post::factory()->count(3)->create();
        Post::factory()->create(['title' => 'Unik sekali']);

        $this->assertSame(4, $this->repo->paginateAll(null, 10)->total());
        $this->assertSame(1, $this->repo->paginateAll('unik', 10)->total());
        $this->assertCount(4, $this->repo->all());
    }

    public function test_create_update_delete_and_slug_exists(): void
    {
        $user = User::factory()->create();

        $post = $this->repo->create($user->id, [
            'title' => 'Baru', 'slug' => 'baru', 'category' => 'Umum', 'content' => 'x', 'is_published' => false,
        ]);

        $this->assertSame($user->id, $post->user_id);
        $this->assertTrue($this->repo->slugExists('baru'));
        $this->assertFalse($this->repo->slugExists('lain'));

        $post = $this->repo->update($post, ['title' => 'Diubah']);
        $this->assertSame('Diubah', $post->fresh()->title);

        $this->repo->delete($post);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
