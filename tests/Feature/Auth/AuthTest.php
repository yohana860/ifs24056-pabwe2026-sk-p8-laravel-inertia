<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_pages_render_for_guests(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
        $this->get('/register')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
    }

    public function test_authenticated_users_are_redirected_away_from_auth_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/login')->assertRedirect('/');
        $this->get('/register')->assertRedirect('/');
    }

    public function test_member_can_login_with_username(): void
    {
        $user = User::factory()->create(['username' => 'penulis1', 'password' => 'secret123']);

        $this->post('/login', ['login' => 'penulis1', 'password' => 'secret123'])
            ->assertRedirect(route('posts.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_email_and_remember_me(): void
    {
        $user = User::factory()->create(['email' => 'a@b.test', 'password' => 'secret123']);

        $this->post('/login', ['login' => 'a@b.test', 'password' => 'secret123', 'remember' => true])
            ->assertRedirect(route('posts.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_is_redirected_to_admin_panel(): void
    {
        User::factory()->admin()->create(['username' => 'boss', 'password' => 'secret123']);

        $this->post('/login', ['login' => 'boss', 'password' => 'secret123'])
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_login_redirects_to_intended_url(): void
    {
        User::factory()->create(['username' => 'penulis1', 'password' => 'secret123']);

        $this->get('/profile')->assertRedirect('/login');
        $this->post('/login', ['login' => 'penulis1', 'password' => 'secret123'])->assertRedirect('/profile');
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['username' => 'penulis1', 'password' => 'secret123']);

        $this->post('/login', ['login' => 'penulis1', 'password' => 'salah'])
            ->assertSessionHasErrors(['login' => 'Username/email atau password salah.']);

        $this->assertGuest();
    }

    public function test_failed_login_keeps_the_typed_username_and_page_shares_csrf_token(): void
    {
        User::factory()->create(['username' => 'penulis1', 'password' => 'secret123']);

        $this->from('/login')->post('/login', ['login' => 'penulis1', 'password' => 'salah'])->assertRedirect('/login');

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('login', 'penulis1')
            ->where('csrf_token', fn ($token) => is_string($token) && $token !== ''));
    }

    public function test_login_requires_fields(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['login', 'password']);
    }

    public function test_register_creates_member_and_logs_in(): void
    {
        $this->post('/register', [
            'name' => 'Penulis Baru',
            'username' => 'penulis_baru',
            'email' => 'baru@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertRedirect(route('posts.index'))->assertSessionHas('success');

        $user = User::where('username', 'penulis_baru')->firstOrFail();
        $this->assertSame(User::ROLE_MEMBER, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_validation(): void
    {
        User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@example.test']);

        $this->post('/register', [
            'name' => '',
            'username' => 'dipakai',
            'email' => 'dipakai@example.test',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['name', 'username', 'email', 'password']);

        $this->post('/register', [
            'name' => 'X', 'username' => 'spasi salah', 'email' => 'x@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['username']);

        $this->assertGuest();
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_guests_cannot_logout(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }
}
