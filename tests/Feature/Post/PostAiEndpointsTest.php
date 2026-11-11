<?php

namespace Tests\Feature\Post;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Enums\PostCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostAiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function items(int $n = 2): array
    {
        return array_map(fn ($i) => [
            'title' => "Artikel AI {$i}", 'category' => 'Teknologi', 'excerpt' => 'Ringkas', 'content' => "Isi {$i}",
        ], range(1, $n));
    }

    public function test_generate_returns_items_from_openai(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode(['articles' => $this->items()])]]]])]);

        $this->actingAs($this->user)->postJson('/dashboard/posts/ai-generate', ['topic' => 'belajar laravel', 'quantity' => 2, 'category' => 'Teknologi'])
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.title', 'Artikel AI 1');
    }

    public function test_generate_never_exposes_api_key_in_response(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode(['articles' => $this->items(1)])]]]])]);

        $response = $this->actingAs($this->user)->postJson('/dashboard/posts/ai-generate', ['topic' => 'topik', 'quantity' => 1]);

        $this->assertStringNotContainsString('test-openai-key', $response->getContent());
    }

    public function test_generate_handles_ai_errors_with_502(): void
    {
        Http::fake(['*' => Http::response(['error' => 'boom'], 500)]);

        $this->actingAs($this->user)->postJson('/dashboard/posts/ai-generate', ['topic' => 'topik', 'quantity' => 1])
            ->assertStatus(502)
            ->assertJsonPath('message', 'OpenAI mengembalikan error (HTTP 500).');
    }

    public function test_generate_can_use_a_fake_ai_client(): void
    {
        $this->mock(AiClientInterface::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->with('topik', 3, null)
                ->andReturn([new GeneratedPostItem('Judul', PostCategory::Opini, null, 'Isi')]);
        });

        $this->actingAs($this->user)->postJson('/dashboard/posts/ai-generate', ['topic' => 'topik', 'quantity' => 3])
            ->assertOk()
            ->assertJson(['items' => [['title' => 'Judul', 'category' => 'Opini', 'excerpt' => null, 'content' => 'Isi']]]);
    }

    public function test_generate_validates_quantity_bounds_and_topic(): void
    {
        $this->actingAs($this->user);

        $this->postJson('/dashboard/posts/ai-generate', ['topic' => 'topik', 'quantity' => 0])->assertJsonValidationErrors('quantity');
        $this->postJson('/dashboard/posts/ai-generate', ['topic' => 'topik', 'quantity' => 6])->assertJsonValidationErrors('quantity');
        $this->postJson('/dashboard/posts/ai-generate', ['topic' => 'topik', 'quantity' => 'abc'])->assertJsonValidationErrors('quantity');
        $this->postJson('/dashboard/posts/ai-generate', ['topic' => 'ab', 'quantity' => 1])->assertJsonValidationErrors('topic');
        $this->postJson('/dashboard/posts/ai-generate', ['topic' => 'topik', 'quantity' => 1, 'category' => 'Fiksi'])->assertJsonValidationErrors('category');
    }

    public function test_generate_requires_authentication(): void
    {
        $this->postJson('/dashboard/posts/ai-generate', ['topic' => 'topik', 'quantity' => 1])->assertUnauthorized();
    }

    public function test_save_stores_ai_batch_as_drafts(): void
    {
        $this->actingAs($this->user)->post('/dashboard/posts/ai-save', ['items' => $this->items(3)])
            ->assertRedirect('/dashboard/posts')
            ->assertSessionHas('success', '3 artikel AI berhasil disimpan sebagai draf.');

        $this->assertSame(3, $this->user->posts()->count());
        $this->assertSame(0, $this->user->posts()->where('is_published', true)->count());
    }

    public function test_save_validates_items(): void
    {
        $this->actingAs($this->user);

        $this->post('/dashboard/posts/ai-save', [])->assertSessionHasErrors('items');
        $this->post('/dashboard/posts/ai-save', ['items' => $this->items(6)])->assertSessionHasErrors('items');
        $this->post('/dashboard/posts/ai-save', ['items' => [['title' => '', 'category' => 'Fiksi', 'content' => '']]])
            ->assertSessionHasErrors(['items.0.title', 'items.0.category', 'items.0.content']);
    }
}
