<?php

namespace Database\Factories;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state (artikel terbit).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->sentence(6), '.');

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 99999999),
            'category' => fake()->randomElement(PostCategory::values()),
            'excerpt' => fake()->sentence(12),
            'content' => "## ".fake()->sentence(3)."\n\n".fake()->paragraph(4)."\n\n- ".fake()->sentence(4)."\n- ".fake()->sentence(4),
            'cover_image_path' => null,
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    /**
     * Artikel berstatus draf.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * Artikel berstatus terbit.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
