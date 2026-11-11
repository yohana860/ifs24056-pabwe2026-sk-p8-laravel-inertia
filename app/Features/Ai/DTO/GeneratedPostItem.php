<?php

namespace App\Features\Ai\DTO;

use App\Features\Post\Enums\PostCategory;

final readonly class GeneratedPostItem
{
    public function __construct(
        public string $title,
        public PostCategory $category,
        public ?string $excerpt,
        public string $content,
    ) {}

    /**
     * @return array{title: string, category: string, excerpt: string|null, content: string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'category' => $this->category->value,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
        ];
    }
}
