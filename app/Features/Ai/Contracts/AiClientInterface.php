<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Exceptions\AiGenerationException;

interface AiClientInterface
{
    /**
     * Hasilkan draft artikel dari sebuah topik.
     *
     * @return list<GeneratedPostItem>
     *
     * @throws AiGenerationException
     */
    public function generate(string $topic, int $quantity, ?string $category = null): array;
}
