<?php

namespace Tests\Feature\Public;

use App\Features\Post\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_hero_and_only_published_posts(): void
    {
        Post::factory()->count(2)->create();
        Post::factory()->draft()->create(['title' => 'Draf Rahasia']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->where('hero', true)
                ->has('posts.data', 2)
                ->has('categories', 6)
                ->missing('posts.data.0.content'));
    }

    public function test_articles_index_supports_search_and_category_filter(): void
    {
        Post::factory()->create(['title' => 'Belajar Laravel', 'category' => 'Teknologi', 'content' => 'isi']);
        Post::factory()->create(['title' => 'Resep kopi', 'category' => 'Gaya Hidup', 'content' => 'seduh manual']);

        $this->get('/articles')->assertInertia(fn (Assert $page) => $page->where('hero', false)->has('posts.data', 2));

        $this->get('/articles?search=laravel')->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)->where('posts.data.0.title', 'Belajar Laravel')->where('filters.search', 'laravel'));

        $this->get('/articles?search=SEDUH')->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));

        $this->get('/articles?category=Gaya Hidup')->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)->where('filters.category', 'Gaya Hidup'));

        $this->get('/articles?search=tidak-ada-sama-sekali')->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));
    }

    public function test_show_renders_published_article_with_related_posts(): void
    {
        $post = Post::factory()->create(['slug' => 'artikel-satu', 'category' => 'Opini', 'cover_image_path' => 'covers/c.jpg']);
        Post::factory()->create(['category' => 'Opini']);

        $this->get('/articles/artikel-satu')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostShow')
                ->where('post.id', $post->id)
                ->where('post.content', $post->content)
                ->where('post.user.name', $post->user->name)
                ->where('post.cover_url', fn ($url) => str_ends_with($url, 'covers/c.jpg'))
                ->has('related', 1));
    }

    public function test_draft_and_unknown_articles_return_404(): void
    {
        Post::factory()->draft()->create(['slug' => 'masih-draf']);

        $this->get('/articles/masih-draf')->assertNotFound();
        $this->get('/articles/tidak-ada')->assertNotFound();
    }

    public function test_public_pages_do_not_require_login(): void
    {
        foreach (['/', '/articles', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
