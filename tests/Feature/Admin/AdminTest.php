<?php

namespace Tests\Feature\Admin;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    }

    private function rowsOf(string $binary): array
    {
        $path = tempnam(sys_get_temp_dir(), 'a').'.xlsx';
        file_put_contents($path, $binary);

        return IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, false);
    }

    /** @return list<string> */
    private function adminUrls(User $target): array
    {
        return ['/admin/users', "/admin/users/{$target->id}/posts", "/admin/users/{$target->id}/posts/export", '/admin/posts', '/admin/posts/export'];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $member = User::factory()->create();

        foreach ($this->adminUrls($member) as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_members_cannot_access_admin_panel(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member);

        foreach ($this->adminUrls($member) as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_admin_sees_users_with_post_counts_and_can_search(): void
    {
        $writer = User::factory()->create(['name' => 'Budi Penulis', 'username' => 'budi', 'email' => 'budi@example.test']);
        Post::factory()->for($writer)->count(2)->create();
        Post::factory()->for($writer)->draft()->create();

        $this->actingAs($this->admin)->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserList')
                ->has('users.data', 2));

        $this->get('/admin/users?search=BUDI')->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.posts_count', 3)
            ->where('users.data.0.published_posts_count', 2)
            ->where('filters.search', 'BUDI'));

        $this->get('/admin/users?search=budi@example')->assertInertia(fn (Assert $page) => $page->has('users.data', 1));
        $this->get('/admin/users?search=tidak-ada')->assertInertia(fn (Assert $page) => $page->has('users.data', 0));
    }

    public function test_admin_monitors_all_posts_including_drafts(): void
    {
        Post::factory()->count(2)->create();
        Post::factory()->draft()->create(['title' => 'Draf siapa saja']);

        $this->actingAs($this->admin)->get('/admin/posts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PostList')
                ->has('posts.data', 3)
                ->has('posts.data.0.user'));

        $this->get('/admin/posts?search=siapa')->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
    }

    public function test_admin_monitors_posts_of_a_specific_user(): void
    {
        $writer = User::factory()->create(['name' => 'Budi', 'username' => 'budi']);
        Post::factory()->for($writer)->count(2)->create();
        Post::factory()->for($writer)->draft()->create(['title' => 'Cari saya']);
        Post::factory()->create();

        $this->actingAs($this->admin)->get("/admin/users/{$writer->id}/posts")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserPostList')
                ->where('owner.username', 'budi')
                ->has('posts.data', 3)
                ->where('stats', ['total' => 3, 'published' => 2, 'draft' => 1]));

        $this->get("/admin/users/{$writer->id}/posts?search=cari")->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)->where('filters.search', 'cari'));

        $this->get('/admin/users/99999/posts')->assertNotFound();
    }

    public function test_admin_exports_all_posts_with_author_column(): void
    {
        $writer = User::factory()->create(['name' => 'Budi']);
        Post::factory()->for($writer)->count(2)->create();
        Post::factory()->create();

        $response = $this->actingAs($this->admin)->get('/admin/posts/export');

        $response->assertOk();
        $this->assertStringContainsString('semua-artikel.xlsx', $response->headers->get('Content-Disposition'));
        $rows = $this->rowsOf($response->getContent());
        $this->assertCount(4, $rows);
        $this->assertSame('Penulis', $rows[0][7]);
    }

    public function test_admin_exports_posts_of_one_user_only(): void
    {
        $writer = User::factory()->create(['username' => 'Budi_Santoso']);
        Post::factory()->for($writer)->create(['title' => 'Milik Budi']);
        Post::factory()->create(['title' => 'Milik lain']);

        $response = $this->actingAs($this->admin)->get("/admin/users/{$writer->id}/posts/export");

        $response->assertOk();
        $this->assertStringContainsString('artikel-budi-santoso.xlsx', $response->headers->get('Content-Disposition'));
        $rows = $this->rowsOf($response->getContent());
        $this->assertCount(2, $rows);
        $this->assertSame('Milik Budi', $rows[1][0]);
    }

    public function test_admin_monitoring_is_read_only(): void
    {
        $post = Post::factory()->create(['title' => 'Asli']);
        $this->actingAs($this->admin);

        $this->putJson("/admin/posts/{$post->id}", ['title' => 'Diubah'])->assertStatus(404);
        $this->deleteJson("/admin/posts/{$post->id}")->assertStatus(404);
        $this->postJson('/admin/posts', ['title' => 'Baru'])->assertStatus(405);
        $this->putJson('/admin/posts', ['title' => 'Baru'])->assertStatus(405);
        $this->deleteJson('/admin/users')->assertStatus(405);

        $this->assertSame('Asli', $post->fresh()->title);
        $this->assertDatabaseCount('posts', 1);
    }
}
