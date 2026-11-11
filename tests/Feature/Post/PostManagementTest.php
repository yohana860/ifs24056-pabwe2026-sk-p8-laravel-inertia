<?php

namespace Tests\Feature\Post;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\FakeImage;
use Tests\TestCase;

class PostManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Artikel Baru',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkasan singkat',
            'content' => "## Halo\n\nIsi artikel",
            'is_published' => true,
        ], $override);
    }

    public function test_guests_are_redirected_to_login_on_every_dashboard_route(): void
    {
        $post = Post::factory()->create();

        foreach ([
            ['get', '/dashboard/posts'],
            ['post', '/dashboard/posts'],
            ['get', "/dashboard/posts/{$post->id}"],
            ['put', "/dashboard/posts/{$post->id}"],
            ['delete', "/dashboard/posts/{$post->id}"],
            ['patch', "/dashboard/posts/{$post->id}/publish"],
            ['post', "/dashboard/posts/{$post->id}/cover"],
            ['delete', "/dashboard/posts/{$post->id}/cover"],
            ['get', '/dashboard/posts/export'],
            ['post', '/dashboard/posts/import'],
            ['post', '/dashboard/posts/ai-generate'],
            ['post', '/dashboard/posts/ai-save'],
            ['get', '/profile'],
        ] as [$method, $url]) {
            $this->{$method}($url)->assertRedirect('/login');
        }
    }

    public function test_index_lists_only_own_posts_with_database_stats(): void
    {
        Post::factory()->for($this->user)->count(2)->create();
        Post::factory()->for($this->user)->draft()->create();
        Post::factory()->create(); // milik orang lain

        $this->actingAs($this->user)->get('/dashboard/posts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Index')
                ->has('posts.data', 3)
                ->where('stats', ['total' => 3, 'published' => 2, 'draft' => 1])
                ->where('ai', ['min' => 1, 'max' => 5])
                ->has('categories', 6)
                ->where('posts.data.0.content', fn ($content) => is_string($content)));
    }

    public function test_index_search_filters_own_posts(): void
    {
        Post::factory()->for($this->user)->create(['title' => 'Kopi pagi']);
        Post::factory()->for($this->user)->create(['title' => 'Teh sore']);

        $this->actingAs($this->user)->get('/dashboard/posts?search=kopi')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('filters.search', 'kopi'));
    }

    public function test_store_creates_published_post_for_current_user(): void
    {
        $this->actingAs($this->user)->post('/dashboard/posts', $this->payload())
            ->assertRedirect('/dashboard/posts')
            ->assertSessionHas('success');

        $post = Post::firstOrFail();
        $this->assertSame($this->user->id, $post->user_id);
        $this->assertSame('artikel-baru', $post->slug);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
    }

    public function test_store_draft_with_cover_and_duplicate_title_gets_unique_slug(): void
    {
        $this->actingAs($this->user)->post('/dashboard/posts', $this->payload(['is_published' => false]));
        $this->actingAs($this->user)->post('/dashboard/posts', $this->payload([
            'is_published' => false,
            'cover' => FakeImage::make('cover.jpg'),
        ]))->assertSessionHasNoErrors();

        $second = Post::orderByDesc('id')->first();
        $this->assertSame('artikel-baru-2', $second->slug);
        $this->assertNull($second->published_at);
        Storage::disk('public')->assertExists($second->cover_image_path);
    }

    public function test_store_validation_errors(): void
    {
        $this->actingAs($this->user)->post('/dashboard/posts', [])
            ->assertSessionHasErrors(['title', 'category', 'content', 'is_published']);

        $this->actingAs($this->user)->post('/dashboard/posts', $this->payload(['category' => 'Fiksi']))
            ->assertSessionHasErrors('category');

        $this->actingAs($this->user)->post('/dashboard/posts', $this->payload([
            'cover' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors('cover');

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_show_own_post(): void
    {
        $post = Post::factory()->for($this->user)->create();

        $this->actingAs($this->user)->get("/dashboard/posts/{$post->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Show')
                ->where('post.id', $post->id)
                ->where('post.content', $post->content)
                ->has('categories', 6));
    }

    public function test_user_cannot_access_or_modify_other_users_posts(): void
    {
        $post = Post::factory()->create(['title' => 'Milik orang lain']);
        $this->actingAs($this->user);

        $this->get("/dashboard/posts/{$post->id}")->assertForbidden();
        $this->put("/dashboard/posts/{$post->id}", $this->payload())->assertForbidden();
        $this->delete("/dashboard/posts/{$post->id}")->assertForbidden();
        $this->patch("/dashboard/posts/{$post->id}/publish")->assertForbidden();
        $this->post("/dashboard/posts/{$post->id}/cover", ['cover' => FakeImage::make('a.jpg')])->assertForbidden();
        $this->delete("/dashboard/posts/{$post->id}/cover")->assertForbidden();

        $this->assertSame('Milik orang lain', $post->fresh()->title);
    }

    public function test_admin_cannot_modify_posts_of_others_through_dashboard(): void
    {
        $post = Post::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->get("/dashboard/posts/{$post->id}")->assertForbidden();
        $this->put("/dashboard/posts/{$post->id}", $this->payload())->assertForbidden();
        $this->delete("/dashboard/posts/{$post->id}")->assertForbidden();
    }

    public function test_non_numeric_or_missing_post_ids_return_404(): void
    {
        $this->actingAs($this->user);

        $this->get('/dashboard/posts/abc')->assertNotFound();
        $this->get('/dashboard/posts/99999')->assertNotFound();
    }

    public function test_update_own_post(): void
    {
        $post = Post::factory()->for($this->user)->create(['title' => 'Lama', 'slug' => 'lama']);

        $this->actingAs($this->user)->from('/dashboard/posts')
            ->put("/dashboard/posts/{$post->id}", $this->payload(['title' => 'Baru', 'is_published' => false]))
            ->assertRedirect('/dashboard/posts')
            ->assertSessionHas('success');

        $post->refresh();
        $this->assertSame('Baru', $post->title);
        $this->assertSame('lama', $post->slug);
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
    }

    public function test_update_with_multipart_method_spoofing_and_cover(): void
    {
        $post = Post::factory()->for($this->user)->create();

        $this->actingAs($this->user)->post("/dashboard/posts/{$post->id}", $this->payload([
            '_method' => 'put',
            'cover' => FakeImage::make('baru.png'),
        ]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists($post->fresh()->cover_image_path);
    }

    public function test_update_validation(): void
    {
        $post = Post::factory()->for($this->user)->create();

        $this->actingAs($this->user)->put("/dashboard/posts/{$post->id}", ['title' => ''])
            ->assertSessionHasErrors(['title', 'category', 'content', 'is_published']);
    }

    public function test_destroy_own_post_removes_cover_file(): void
    {
        $post = Post::factory()->for($this->user)->create(['cover_image_path' => FakeImage::make('c.jpg')->store('covers', 'public')]);
        $path = $post->cover_image_path;

        $this->actingAs($this->user)->delete("/dashboard/posts/{$post->id}")
            ->assertRedirect('/dashboard/posts')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_publish_toggle_switches_between_draft_and_published(): void
    {
        $post = Post::factory()->for($this->user)->draft()->create();
        $this->actingAs($this->user)->from('/dashboard/posts');

        $this->patch("/dashboard/posts/{$post->id}/publish")->assertRedirect('/dashboard/posts')->assertSessionHas('success', 'Artikel dipublikasikan.');
        $this->assertTrue($post->fresh()->is_published);
        $this->assertNotNull($post->fresh()->published_at);

        $this->patch("/dashboard/posts/{$post->id}/publish")->assertSessionHas('success', 'Artikel dikembalikan menjadi draf.');
        $this->assertFalse($post->fresh()->is_published);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_cover_upload_replace_and_delete(): void
    {
        $post = Post::factory()->for($this->user)->create();
        $this->actingAs($this->user);

        $this->post("/dashboard/posts/{$post->id}/cover", ['cover' => FakeImage::make('a.jpg')])->assertSessionHas('success');
        $first = $post->fresh()->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->post("/dashboard/posts/{$post->id}/cover", ['cover' => FakeImage::make('b.webp')]);
        Storage::disk('public')->assertMissing($first);

        $current = $post->fresh()->cover_image_path;
        $this->delete("/dashboard/posts/{$post->id}/cover")->assertSessionHas('success');
        Storage::disk('public')->assertMissing($current);
        $this->assertNull($post->fresh()->cover_image_path);
    }

    public function test_cover_upload_validation(): void
    {
        $post = Post::factory()->for($this->user)->create();
        $this->actingAs($this->user);

        $this->post("/dashboard/posts/{$post->id}/cover", [])->assertSessionHasErrors('cover');
        $this->post("/dashboard/posts/{$post->id}/cover", ['cover' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')])->assertSessionHasErrors('cover');
        $this->post("/dashboard/posts/{$post->id}/cover", ['cover' => FakeImage::make('big.jpg')->size(4096)])->assertSessionHasErrors('cover');

        $this->assertNull($post->fresh()->cover_image_path);
    }
}
