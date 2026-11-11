<?php

namespace App\Features\Admin\Controllers;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request, AdminUserService $users): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Admin/UserList', [
            'users' => $users->paginateUsers($search),
            'filters' => ['search' => $search],
        ]);
    }

    /** Monitoring artikel user tertentu (read-only). */
    public function posts(Request $request, User $user, PostService $posts): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Admin/UserPostList', [
            'owner' => $user->only(['id', 'name', 'username', 'email']),
            'posts' => $posts->paginateForUser($user, $search),
            'stats' => $posts->statsForUser($user),
            'filters' => ['search' => $search],
        ]);
    }

    public function exportPosts(User $user, PostService $posts, PostExcelService $excel): HttpResponse
    {
        return $excel->download(
            $posts->allForUser($user),
            'artikel-'.Str::slug($user->username).'.xlsx',
        );
    }
}
