<?php

namespace Tests\Feature\Support;

use App\Http\Middleware\EnsurePostOwnership;
use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_middleware_allows_matching_role(): void
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => User::factory()->admin()->make());

        $response = (new EnsureRole)->handle($request, fn () => response('ok'), 'admin');

        $this->assertSame('ok', $response->getContent());
    }

    public function test_role_middleware_blocks_other_roles_and_guests(): void
    {
        $member = Request::create('/');
        $member->setUserResolver(fn () => User::factory()->make());

        foreach ([$member, Request::create('/')] as $request) {
            try {
                (new EnsureRole)->handle($request, fn () => response('ok'), 'admin');
                $this->fail('Middleware seharusnya menolak request.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }

    public function test_post_ownership_middleware_rejects_request_without_post_binding(): void
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => User::factory()->make());

        $this->expectException(HttpException::class);

        (new EnsurePostOwnership)->handle($request, fn () => response('ok'));
    }

    public function test_inertia_shares_guest_state(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', null)
            ->where('appName', config('app.name'))
            ->where('flash.success', null)
            ->where('flash.error', null));
    }

    public function test_inertia_shares_authenticated_user_and_flash_messages(): void
    {
        $user = User::factory()->admin()->create(['username' => 'boss']);

        $this->actingAs($user)
            ->withSession(['success' => 'Berhasil', 'error' => 'Gagal'])
            ->get('/dashboard/posts')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.username', 'boss')
                ->where('auth.user.role', 'admin')
                ->where('auth.user.is_admin', true)
                ->where('auth.user.profile_photo_url', null)
                ->missing('auth.user.password')
                ->where('flash.success', 'Berhasil')
                ->where('flash.error', 'Gagal'));
    }
}
