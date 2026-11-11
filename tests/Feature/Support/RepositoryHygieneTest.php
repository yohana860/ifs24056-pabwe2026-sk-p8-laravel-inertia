<?php

namespace Tests\Feature\Support;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Tests\TestCase;

class RepositoryHygieneTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    public static function storageDirectories(): array
    {
        return [
            'storage/app',
            'storage/app/private',
            'storage/app/public',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/testing',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ];
    }

    public function test_every_storage_directory_keeps_a_gitignore_file(): void
    {
        foreach (self::storageDirectories() as $directory) {
            $file = base_path($directory.'/.gitignore');

            $this->assertFileExists($file, "{$directory}/.gitignore hilang (dibutuhkan agar `php artisan view:clear` lolos di CI).");
        }
    }

    public function test_storage_gitignore_files_ignore_everything_except_themselves(): void
    {
        foreach (['storage/app/public', 'storage/app/private', 'storage/framework/cache/data',
            'storage/framework/sessions', 'storage/framework/testing', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $directory) {
            $lines = array_values(array_filter(array_map('trim', file(base_path($directory.'/.gitignore')))));

            $this->assertSame(['*', '!.gitignore'], $lines, $directory);
        }

        // storage/framework/cache menyimpan sub-direktori data/ agar ikut terlacak.
        $cache = array_values(array_filter(array_map('trim', file(base_path('storage/framework/cache/.gitignore')))));
        $this->assertSame(['*', '!data/', '!.gitignore'], $cache);
    }

    public function test_seeder_creates_default_accounts_and_sample_posts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();
        $member = User::where('username', 'member')->firstOrFail();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('admin', $admin->password));
        $this->assertTrue(Hash::check('member', $member->password));
        $this->assertSame(7, $member->posts()->count());
        $this->assertSame(1, $member->posts()->where('is_published', false)->count());
    }
}
