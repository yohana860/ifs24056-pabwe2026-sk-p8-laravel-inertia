<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminPostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    /** Monitoring seluruh artikel sistem (read-only). */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Admin/PostList', [
            'posts' => $this->posts->paginateAll($search),
            'filters' => ['search' => $search],
        ]);
    }

    public function export(PostExcelService $excel): HttpResponse
    {
        return $excel->download($this->posts->all(), 'semua-artikel.xlsx', true);
    }
}
