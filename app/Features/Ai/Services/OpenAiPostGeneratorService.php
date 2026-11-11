<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Exceptions\AiGenerationException;
use App\Features\Post\Enums\PostCategory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenAiPostGeneratorService implements AiClientInterface
{
    public function generate(string $topic, int $quantity, ?string $category = null): array
    {
        $apiKey = config('openai.api_key');

        if (blank($apiKey)) {
            throw new AiGenerationException('OPENAI_API_KEY belum dikonfigurasi di server.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(config('openai.timeout'))
                ->post(config('openai.base_url').'/chat/completions', [
                    'model' => config('openai.model'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $this->userPrompt($topic, $quantity, $category)],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiGenerationException('Tidak dapat terhubung ke layanan OpenAI.', 0, $e);
        }

        if ($response->failed()) {
            throw new AiGenerationException('OpenAI mengembalikan error (HTTP '.$response->status().').');
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        $articles = is_array($decoded) ? ($decoded['articles'] ?? null) : null;

        if (! is_array($articles)) {
            throw new AiGenerationException('Format respons OpenAI tidak valid.');
        }

        $items = [];

        foreach ($articles as $article) {
            $item = $this->toItem($article);

            if ($item !== null) {
                $items[] = $item;
            }
        }

        if ($items === []) {
            throw new AiGenerationException('OpenAI tidak menghasilkan artikel yang valid.');
        }

        return $items;
    }

    private function toItem(mixed $article): ?GeneratedPostItem
    {
        if (! is_array($article)) {
            return null;
        }

        $title = trim((string) ($article['title'] ?? ''));
        $content = trim((string) ($article['content'] ?? ''));

        if ($title === '' || $content === '') {
            return null;
        }

        $excerpt = trim((string) ($article['excerpt'] ?? ''));

        return new GeneratedPostItem(
            title: mb_substr($title, 0, 255),
            category: PostCategory::tryFrom((string) ($article['category'] ?? '')) ?? PostCategory::Umum,
            excerpt: $excerpt === '' ? null : $excerpt,
            content: $content,
        );
    }

    private function systemPrompt(): string
    {
        return 'Kamu adalah penulis blog berbahasa Indonesia. Balas HANYA dengan JSON valid berbentuk '
            .'{"articles":[{"title":"...","category":"...","excerpt":"...","content":"..."}]}. '
            .'"content" ditulis dalam format Markdown. "category" harus salah satu dari: '
            .implode(', ', PostCategory::values()).'.';
    }

    private function userPrompt(string $topic, int $quantity, ?string $category): string
    {
        $prompt = "Tulis {$quantity} artikel blog dengan topik: {$topic}.";

        if ($category !== null && $category !== '') {
            $prompt .= " Gunakan kategori \"{$category}\".";
        }

        return $prompt;
    }
}
