<?php

namespace App\Features\Post\Controllers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Exceptions\AiGenerationException;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Requests\AiGenerateRequest;
use App\Features\Post\Requests\AiSaveRequest;
use App\Features\Post\Requests\CoverRequest;
use App\Features\Post\Requests\ImportPostRequest;
use App\Features\Post\Requests\StorePostRequest;
use App\Features\Post\Requests\UpdatePostRequest;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('search')->toString();

        return Inertia::render('Post/Index', [
            'posts' => $this->posts->paginateForUser($user, $search),
            'stats' => $this->posts->statsForUser($user),
            'filters' => ['search' => $search],
            'categories' => PostCategory::options(),
            'ai' => [
                'min' => config('openai.min_quantity'),
                'max' => config('openai.max_quantity'),
            ],
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->posts->create($request->user(), $request->validated(), $request->file('cover'));

        return redirect()->route('posts.index')->with('success', 'Artikel berhasil dibuat.');
    }

    public function show(Post $post): Response
    {
        return Inertia::render('Post/Show', [
            'post' => $post->load('user:id,name,username'),
            'categories' => PostCategory::options(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->posts->update($post, $request->validated(), $request->file('cover'));

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->posts->delete($post);

        return redirect()->route('posts.index')->with('success', 'Artikel berhasil dihapus.');
    }

    public function publish(Post $post): RedirectResponse
    {
        $post = $this->posts->togglePublish($post);

        return back()->with('success', $post->is_published ? 'Artikel dipublikasikan.' : 'Artikel dikembalikan menjadi draf.');
    }

    public function uploadCover(CoverRequest $request, Post $post): RedirectResponse
    {
        $this->posts->storeCover($post, $request->file('cover'));

        return back()->with('success', 'Cover artikel berhasil diunggah.');
    }

    public function destroyCover(Post $post): RedirectResponse
    {
        $this->posts->removeCover($post);

        return back()->with('success', 'Cover artikel berhasil dihapus.');
    }

    /** Ekspor HANYA artikel milik pengguna yang login. */
    public function export(Request $request, PostExcelService $excel): HttpResponse
    {
        return $excel->download($this->posts->allForUser($request->user()), 'artikel-saya.xlsx');
    }

    public function import(ImportPostRequest $request, PostExcelService $excel): RedirectResponse
    {
        try {
            $items = $excel->parse($request->file('file'));
        } catch (SpreadsheetException) {
            return back()->with('error', 'File Excel tidak dapat dibaca. Pastikan formatnya benar.');
        }

        if ($items === []) {
            return back()->with('error', 'Tidak ada baris artikel yang valid pada file Excel.');
        }

        $count = $this->posts->createMany($request->user(), $items);

        return back()->with('success', "{$count} artikel berhasil diimpor.");
    }

    /** Hasilkan draft artikel via OpenAI (JSON); API key tidak pernah dikirim ke frontend. */
    public function aiGenerate(AiGenerateRequest $request, AiClientInterface $ai): JsonResponse
    {
        try {
            $items = $ai->generate(
                $request->string('topic')->toString(),
                $request->integer('quantity'),
                $request->input('category'),
            );
        } catch (AiGenerationException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'items' => array_map(fn (GeneratedPostItem $item) => $item->toArray(), $items),
        ]);
    }

    /** Simpan batch artikel hasil AI sebagai draf. */
    public function aiSave(AiSaveRequest $request): RedirectResponse
    {
        $items = array_map(
            fn (array $item) => $item + ['is_published' => false],
            $request->validated('items')
        );

        $count = $this->posts->createMany($request->user(), $items);

        return redirect()->route('posts.index')->with('success', "{$count} artikel AI berhasil disimpan sebagai draf.");
    }
}
