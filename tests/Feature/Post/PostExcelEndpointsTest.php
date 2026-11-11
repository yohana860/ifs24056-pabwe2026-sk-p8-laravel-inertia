<?php

namespace Tests\Feature\Post;

use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class PostExcelEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function rowsOf(string $binary): array
    {
        $path = tempnam(sys_get_temp_dir(), 'e').'.xlsx';
        file_put_contents($path, $binary);

        return IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, false);
    }

    private function xlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $spreadsheet->getActiveSheet()->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'i').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return new UploadedFile($path, 'artikel.xlsx', null, null, true);
    }

    public function test_member_exports_only_own_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->create(['title' => 'Punya saya']);
        Post::factory()->create(['title' => 'Punya orang lain']);

        $response = $this->actingAs($user)->get('/dashboard/posts/export');

        $response->assertOk();
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('artikel-saya.xlsx', $response->headers->get('Content-Disposition'));

        $rows = $this->rowsOf($response->getContent());
        $this->assertCount(2, $rows); // header + 1 artikel
        $this->assertSame('Punya saya', $rows[1][0]);
    }

    public function test_member_imports_posts_from_excel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/posts/import', ['file' => $this->xlsx([
            ['Judul', 'Kategori', 'Ringkasan', 'Isi', 'Status'],
            ['Impor Satu', 'Bisnis', 'Ringkas', 'Isi satu', 'Published'],
            ['Impor Dua', 'Tidak dikenal', '', 'Isi dua', 'Draft'],
        ])])->assertSessionHas('success', '2 artikel berhasil diimpor.');

        $this->assertSame(2, $user->posts()->count());
        $this->assertTrue(Post::where('title', 'Impor Satu')->firstOrFail()->is_published);
        $this->assertSame('Umum', Post::where('title', 'Impor Dua')->firstOrFail()->category->value);
    }

    public function test_import_without_valid_rows_reports_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/posts/import', ['file' => $this->xlsx([['Judul', 'Kategori', 'Ringkasan', 'Isi', 'Status']])])
            ->assertSessionHas('error', 'Tidak ada baris artikel yang valid pada file Excel.');

        $this->assertSame(0, $user->posts()->count());
    }

    public function test_import_reports_unreadable_file(): void
    {
        $this->mock(PostExcelService::class, function ($mock) {
            $mock->shouldReceive('parse')->once()->andThrow(new SpreadsheetException('rusak'));
        });

        $this->actingAs(User::factory()->create())
            ->post('/dashboard/posts/import', ['file' => UploadedFile::fake()->create('rusak.xlsx', 5)])
            ->assertSessionHas('error', 'File Excel tidak dapat dibaca. Pastikan formatnya benar.');
    }

    public function test_import_validates_file(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->post('/dashboard/posts/import', [])->assertSessionHasErrors('file');
        $this->post('/dashboard/posts/import', ['file' => UploadedFile::fake()->create('x.pdf', 5, 'application/pdf')])->assertSessionHasErrors('file');
        $this->post('/dashboard/posts/import', ['file' => UploadedFile::fake()->create('besar.xlsx', 4096)])->assertSessionHasErrors('file');
    }
}
