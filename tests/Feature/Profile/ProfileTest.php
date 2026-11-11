<?php

namespace Tests\Feature\Profile;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create(['username' => 'lama', 'email' => 'lama@example.test', 'password' => 'passwordlama']);
    }

    private function payload(array $override = []): array
    {
        return array_merge(['name' => 'Nama Baru', 'username' => 'baru', 'email' => 'baru@example.test'], $override);
    }

    public function test_profile_page_shows_stats(): void
    {
        Post::factory()->for($this->user)->create();
        Post::factory()->for($this->user)->draft()->create();

        $this->actingAs($this->user)->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('stats', ['total' => 2, 'published' => 1, 'draft' => 1])
                ->where('auth.user.username', 'lama'));
    }

    public function test_update_basic_fields_without_changing_password(): void
    {
        $this->actingAs($this->user)->put('/profile', $this->payload())
            ->assertRedirect('/profile')
            ->assertSessionHas('success');

        $this->user->refresh();
        $this->assertSame('Nama Baru', $this->user->name);
        $this->assertSame('baru', $this->user->username);
        $this->assertTrue(Hash::check('passwordlama', $this->user->password));
    }

    public function test_keeping_own_username_and_email_is_allowed(): void
    {
        $this->actingAs($this->user)->put('/profile', $this->payload(['username' => 'lama', 'email' => 'lama@example.test']))
            ->assertSessionHasNoErrors();
    }

    public function test_username_and_email_must_be_unique(): void
    {
        User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@example.test']);

        $this->actingAs($this->user)->put('/profile', $this->payload(['username' => 'dipakai', 'email' => 'dipakai@example.test']))
            ->assertSessionHasErrors(['username', 'email']);
    }

    public function test_change_password_requires_current_password(): void
    {
        $this->actingAs($this->user);

        $this->put('/profile', $this->payload(['password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru']))
            ->assertSessionHasErrors('current_password');

        $this->put('/profile', $this->payload(['current_password' => 'salah', 'password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru']))
            ->assertSessionHasErrors('current_password');

        $this->put('/profile', $this->payload(['current_password' => 'passwordlama', 'password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru']))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('passwordbaru', $this->user->fresh()->password));
    }

    public function test_upload_and_replace_profile_photo(): void
    {
        $this->actingAs($this->user);

        $this->put('/profile', $this->payload(['photo' => UploadedFile::fake()->image('a.jpg')]))->assertSessionHasNoErrors();
        $first = $this->user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $this->put('/profile', $this->payload(['photo' => UploadedFile::fake()->image('b.png')]));
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($this->user->fresh()->profile_photo_path);
    }

    public function test_invalid_photo_is_rejected(): void
    {
        $this->actingAs($this->user)->put('/profile', $this->payload(['photo' => UploadedFile::fake()->create('x.pdf', 5, 'application/pdf')]))
            ->assertSessionHasErrors('photo');
    }
}
