<?php

namespace Database\Seeders;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Akun bawaan: admin / admin dan member / member (username / password).
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@blogverse.test',
            'password' => 'admin',
        ]);

        $member = User::factory()->create([
            'name' => 'Penulis Contoh',
            'username' => 'member',
            'email' => 'member@blogverse.test',
            'password' => 'member',
        ]);

        foreach (PostCategory::cases() as $category) {
            Post::factory()->for($member)->create([
                'title' => "Mengenal dunia {$category->value}",
                'slug' => 'mengenal-dunia-'.str_replace(' ', '-', strtolower($category->value)),
                'category' => $category,
            ]);
        }

        Post::factory()->for($member)->draft()->create([
            'title' => 'Draf artikel yang belum terbit',
            'slug' => 'draf-artikel-yang-belum-terbit',
            'category' => PostCategory::Umum,
        ]);
    }
}
