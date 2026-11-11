<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Services\PostService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    /** Beranda: hero + galeri artikel terbit. */
    public function home(): Response
    {
        return Inertia::render('Public/PostIndex', [
            'hero' => true,
            'posts' => $this->posts->paginatePublic(null, null),
            'categories' => PostCategory::options(),
            'filters' => ['search' => '', 'category' => ''],
        ]);
    }

    /** Daftar artikel publik + pencarian judul/konten + filter kategori. */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();

        return Inertia::render('Public/PostIndex', [
            'hero' => false,
            'posts' => $this->posts->paginatePublic($search, $category),
            'categories' => PostCategory::options(),
            'filters' => ['search' => $search, 'category' => $category],
        ]);
    }

    /** Pembaca artikel; draf/slug tidak dikenal menghasilkan 404. */
    public function show(string $slug): Response
    {
        $post = $this->posts->findPublishedOrFail($slug);

        return Inertia::render('Public/PostShow', [
            'post' => $post,
            'related' => $this->posts->related($post),
        ]);
    }
}
