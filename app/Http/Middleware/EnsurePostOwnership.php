<?php

namespace App\Http\Middleware;

use App\Features\Post\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePostOwnership
{
    /**
     * Pastikan artikel pada route {post} adalah milik pengguna yang sedang login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $post = $request->route('post');

        abort_unless(
            $post instanceof Post && $request->user()?->can('manage', $post),
            403,
            'Anda hanya dapat mengelola artikel milik sendiri.'
        );

        return $next($request);
    }
}
