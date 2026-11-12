<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_seeder_creates_default_accounts_and_sample_posts_and_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class); // dijalankan dua kali: tidak boleh duplikat / error

        $admin = User::where('username', 'admin')->firstOrFail();
        $member = User::where('username', 'member')->firstOrFail();

        $this->assertSame(2, User::count());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($member->isAdmin());
        $this->assertTrue(Hash::check('admin', $admin->password));
        $this->assertTrue(Hash::check('member', $member->password));
        $this->assertSame(7, $member->posts()->count());
        $this->assertSame(1, $member->posts()->where('is_published', false)->count());
        $this->assertSame(6, $member->posts()->where('is_published', true)->whereNotNull('published_at')->count());
    }

    public function test_seeder_does_not_depend_on_dev_only_packages(): void
    {
        $seeder = (string) file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        $this->assertStringNotContainsString('::factory()', $seeder);
        $this->assertStringNotContainsString('fake()', $seeder);
    }
}
