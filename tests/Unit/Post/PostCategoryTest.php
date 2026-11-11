<?php

namespace Tests\Unit\Post;

use App\Features\Post\Enums\PostCategory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostCategoryTest extends TestCase
{
    public function test_it_has_the_six_categories_from_the_module(): void
    {
        $this->assertSame(
            ['Teknologi', 'Pendidikan', 'Gaya Hidup', 'Bisnis', 'Opini', 'Umum'],
            PostCategory::values()
        );
    }

    #[DataProvider('descriptions')]
    public function test_description_matches_module(PostCategory $category, string $expected): void
    {
        $this->assertSame($expected, $category->description());
    }

    /** @return array<string, array{PostCategory, string}> */
    public static function descriptions(): array
    {
        return [
            'teknologi' => [PostCategory::Teknologi, 'Teknologi & Koding'],
            'pendidikan' => [PostCategory::Pendidikan, 'Pendidikan & Tutorial'],
            'gaya hidup' => [PostCategory::GayaHidup, 'Gaya Hidup & Produktivitas'],
            'bisnis' => [PostCategory::Bisnis, 'Bisnis & Karir'],
            'opini' => [PostCategory::Opini, 'Opini & Pemikiran'],
            'umum' => [PostCategory::Umum, 'Umum'],
        ];
    }

    public function test_options_contain_value_and_description(): void
    {
        $options = PostCategory::options();

        $this->assertCount(6, $options);
        $this->assertSame(['value' => 'Gaya Hidup', 'description' => 'Gaya Hidup & Produktivitas'], $options[2]);
    }
}
