<?php

namespace Tests\Unit\Models;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Policies\PostPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_belongs_to_user_and_user_has_many_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->assertTrue($post->user->is($user));
        $this->assertTrue($user->posts->first()->is($post));
    }

    public function test_post_casts(): void
    {
        $post = Post::factory()->create(['category' => 'Gaya Hidup'])->refresh();

        $this->assertSame(PostCategory::GayaHidup, $post->category);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
    }

    public function test_post_cover_url_is_null_without_cover_and_present_with_cover(): void
    {
        $post = Post::factory()->create(['cover_image_path' => null]);
        $this->assertNull($post->cover_url);

        $post->cover_image_path = 'covers/a.jpg';
        $this->assertStringEndsWith('covers/a.jpg', $post->cover_url);
        $this->assertArrayHasKey('cover_url', $post->toArray());
    }

    public function test_user_id_is_not_mass_assignable_on_post(): void
    {
        $other = User::factory()->create();
        $post = new Post(['title' => 'X', 'user_id' => $other->id]);

        $this->assertNull($post->user_id);
    }

    public function test_post_factory_states(): void
    {
        $draft = Post::factory()->draft()->create();
        $published = Post::factory()->draft()->published()->create();

        $this->assertFalse($draft->is_published);
        $this->assertNull($draft->published_at);
        $this->assertTrue($published->is_published);
    }

    public function test_slug_is_unique_in_database(): void
    {
        Post::factory()->create(['slug' => 'sama']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Post::factory()->create(['slug' => 'sama']);
    }

    public function test_deleting_user_cascades_to_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->count(2)->create();

        $user->delete();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_user_roles_and_mass_assignment_protection(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($member->isAdmin());
        $this->assertNull((new User(['name' => 'A', 'role' => User::ROLE_ADMIN]))->role);
    }

    public function test_user_password_is_hashed_and_hidden(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_user_profile_photo_url(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->profile_photo_url);

        $user->profile_photo_path = 'profile-photos/a.jpg';
        $this->assertStringEndsWith('profile-photos/a.jpg', $user->profile_photo_url);
    }

    public function test_post_policy_only_allows_the_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->admin()->create();
        $post = Post::factory()->for($owner)->create();

        $policy = new PostPolicy;

        $this->assertTrue($policy->manage($owner, $post));
        $this->assertFalse($policy->manage($other, $post));
    }
}
