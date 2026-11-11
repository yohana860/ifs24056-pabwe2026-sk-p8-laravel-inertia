<?php

namespace Tests\Unit\Ai;

use App\Features\Ai\Exceptions\AiGenerationException;
use App\Features\Ai\Services\OpenAiPostGeneratorService;
use App\Features\Post\Enums\PostCategory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiPostGeneratorServiceTest extends TestCase
{
    private function fakeCompletion(mixed $content): void
    {
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => $content]]]]),
        ]);
    }

    public function test_it_returns_items_and_sends_authorized_request(): void
    {
        $this->fakeCompletion(json_encode(['articles' => [
            ['title' => 'Belajar Laravel', 'category' => 'Teknologi', 'excerpt' => 'Ringkas', 'content' => '## Isi'],
        ]]));

        $items = (new OpenAiPostGeneratorService)->generate('laravel', 1, 'Teknologi');

        $this->assertCount(1, $items);
        $this->assertSame('Belajar Laravel', $items[0]->title);
        $this->assertSame(PostCategory::Teknologi, $items[0]->category);
        $this->assertSame('Ringkas', $items[0]->excerpt);

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && str_ends_with($request->url(), '/chat/completions')
            && str_contains($request['messages'][1]['content'], 'Teknologi')
            && $request['response_format']['type'] === 'json_object');
    }

    public function test_it_skips_invalid_items_and_normalises_the_rest(): void
    {
        $this->fakeCompletion(json_encode(['articles' => [
            'bukan-array',
            ['title' => '', 'content' => 'tanpa judul'],
            ['title' => 'Tanpa isi', 'content' => ''],
            ['title' => 'Valid', 'category' => 'Tidak Dikenal', 'excerpt' => '', 'content' => 'Isi'],
        ]]));

        $items = (new OpenAiPostGeneratorService)->generate('topik', 4);

        $this->assertCount(1, $items);
        $this->assertSame(PostCategory::Umum, $items[0]->category);
        $this->assertNull($items[0]->excerpt);

        Http::assertSent(fn (Request $request) => ! str_contains($request['messages'][1]['content'], 'Gunakan kategori'));
    }

    public function test_it_requires_an_api_key(): void
    {
        config(['openai.api_key' => null]);
        Http::fake();

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');

        try {
            (new OpenAiPostGeneratorService)->generate('topik', 1);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_it_wraps_connection_errors(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Tidak dapat terhubung');

        (new OpenAiPostGeneratorService)->generate('topik', 1);
    }

    public function test_it_reports_http_errors(): void
    {
        Http::fake(['*' => Http::response(['error' => 'boom'], 500)]);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new OpenAiPostGeneratorService)->generate('topik', 1);
    }

    public function test_it_rejects_non_json_content(): void
    {
        $this->fakeCompletion('ini bukan json');

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Format respons');

        (new OpenAiPostGeneratorService)->generate('topik', 1);
    }

    public function test_it_rejects_json_without_articles_array(): void
    {
        $this->fakeCompletion(json_encode(['articles' => 'salah']));

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Format respons');

        (new OpenAiPostGeneratorService)->generate('topik', 1);
    }

    public function test_it_fails_when_no_item_is_valid(): void
    {
        $this->fakeCompletion(json_encode(['articles' => [['title' => '', 'content' => '']]]));

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('tidak menghasilkan artikel yang valid');

        (new OpenAiPostGeneratorService)->generate('topik', 1);
    }
}
