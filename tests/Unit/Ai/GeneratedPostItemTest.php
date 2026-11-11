<?php

namespace Tests\Unit\Ai;

use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Enums\PostCategory;
use PHPUnit\Framework\TestCase;

class GeneratedPostItemTest extends TestCase
{
    public function test_to_array_uses_enum_value(): void
    {
        $item = new GeneratedPostItem('Judul', PostCategory::GayaHidup, null, 'Isi');

        $this->assertSame(
            ['title' => 'Judul', 'category' => 'Gaya Hidup', 'excerpt' => null, 'content' => 'Isi'],
            $item->toArray()
        );
    }
}
