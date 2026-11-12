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
     * Tidak memakai factory/Faker (paket dev) sehingga aman dijalankan di server produksi
     * (composer install --no-dev) dan aman dijalankan berulang (idempoten).
     */
    public function run(): void
    {
        $this->account('admin', 'Administrator', 'admin@blogverse.test', User::ROLE_ADMIN);
        $member = $this->account('member', 'Penulis Contoh', 'member@blogverse.test', User::ROLE_MEMBER);

        foreach (PostCategory::cases() as $category) {
            $this->post(
                $member,
                "Mengenal dunia {$category->value}",
                'mengenal-dunia-'.str_replace(' ', '-', strtolower($category->value)),
                $category,
                true,
            );
        }

        $this->post($member, 'Draf artikel yang belum terbit', 'draf-artikel-yang-belum-terbit', PostCategory::Umum, false);
    }

    private function account(string $username, string $name, string $email, string $role): User
    {
        $user = User::firstOrNew(['username' => $username]);
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password' => $username, // password = username; di-hash otomatis oleh cast "hashed"
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function post(User $author, string $title, string $slug, PostCategory $category, bool $published): void
    {
        $post = Post::firstOrNew(['slug' => $slug]);
        $post->fill([
            'title' => $title,
            'category' => $category,
            'excerpt' => "Pengantar singkat tentang {$category->description()}.",
            'content' => "## {$title}\n\nIni adalah artikel contoh untuk kategori **{$category->value}**.\n\n- Ditulis dengan Markdown\n- Dirender oleh TOAST UI Viewer",
            'is_published' => $published,
            'published_at' => $published ? ($post->published_at ?? now()) : null,
        ]);
        $post->user_id = $author->id;
        $post->save();
    }
}
