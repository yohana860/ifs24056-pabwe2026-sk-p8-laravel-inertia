<?php

namespace Tests\Unit\Post;

use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class PostExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    private PostExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PostExcelService;
    }

    private function load(string $binary): array
    {
        $path = tempnam(sys_get_temp_dir(), 'x').'.xlsx';
        file_put_contents($path, $binary);

        return IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, false);
    }

    private function upload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'u').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return new UploadedFile($path, 'artikel.xlsx', null, null, true);
    }

    public function test_export_has_headers_and_rows(): void
    {
        $user = User::factory()->create(['name' => 'Penulis A']);
        Post::factory()->for($user)->create(['title' => 'Judul 1', 'category' => 'Bisnis', 'excerpt' => 'Ringkas', 'content' => 'Isi 1']);
        Post::factory()->for($user)->draft()->create(['title' => 'Judul 2', 'excerpt' => null]);

        $rows = $this->load($this->service->export(Post::with('user')->orderBy('id')->get()));

        $this->assertSame(['Judul', 'Kategori', 'Ringkasan', 'Isi', 'Status', 'Dipublikasikan', 'Dibuat'], $rows[0]);
        $this->assertSame('Judul 1', $rows[1][0]);
        $this->assertSame('Bisnis', $rows[1][1]);
        $this->assertSame('Published', $rows[1][4]);
        $this->assertSame('Draft', $rows[2][4]);
        $this->assertEmpty($rows[2][5]);
        $this->assertCount(7, $rows[1]);
    }

    public function test_export_with_author_adds_column(): void
    {
        $user = User::factory()->create(['name' => 'Penulis A']);
        Post::factory()->for($user)->create();

        $rows = $this->load($this->service->export(Post::with('user')->get(), true));

        $this->assertSame('Penulis', $rows[0][7]);
        $this->assertSame('Penulis A', $rows[1][7]);
    }

    public function test_export_writes_formula_like_text_as_plain_string(): void
    {
        Post::factory()->create(['title' => '=1+1', 'content' => '@SUM(A1)']);

        $binary = $this->service->export(Post::with('user')->get());
        $path = tempnam(sys_get_temp_dir(), 'f').'.xlsx';
        file_put_contents($path, $binary);
        $cell = IOFactory::load($path)->getActiveSheet()->getCell('A2');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('=1+1', $cell->getValue());
    }

    public function test_download_returns_xlsx_response(): void
    {
        $response = $this->service->download([], 'a.xlsx');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="a.xlsx"', $response->headers->get('Content-Disposition'));
    }

    public function test_parse_maps_rows_and_skips_invalid_ones(): void
    {
        $file = $this->upload([
            ['Judul', 'Kategori', 'Ringkasan', 'Isi', 'Status'],
            ['Artikel A', 'Bisnis', 'Ringkas A', 'Isi A', 'Published'],
            ['Artikel B', 'Kategori Aneh', '', 'Isi B', 'Draft'],
            ['', 'Umum', '', 'Tanpa judul', 'Draft'],
            ['Tanpa isi', 'Umum', '', '', 'Draft'],
            ['Artikel C', 'Opini', 'Ringkas C', 'Isi C', ' TERBIT '],
        ]);

        $items = $this->service->parse($file);

        $this->assertCount(3, $items);
        $this->assertSame(
            ['title' => 'Artikel A', 'category' => 'Bisnis', 'excerpt' => 'Ringkas A', 'content' => 'Isi A', 'is_published' => true],
            $items[0]
        );
        $this->assertSame('Umum', $items[1]['category']);
        $this->assertNull($items[1]['excerpt']);
        $this->assertFalse($items[1]['is_published']);
        $this->assertTrue($items[2]['is_published']);
    }

    public function test_parse_header_only_file_returns_empty_list(): void
    {
        $this->assertSame([], $this->service->parse($this->upload([['Judul', 'Kategori', 'Ringkasan', 'Isi', 'Status']])));
    }

    public function test_export_then_parse_round_trip(): void
    {
        Post::factory()->create(['title' => 'Bolak balik', 'category' => 'Pendidikan', 'content' => 'Isi **markdown**']);

        $path = tempnam(sys_get_temp_dir(), 'r').'.xlsx';
        file_put_contents($path, $this->service->export(Post::with('user')->get()));

        $items = $this->service->parse(new UploadedFile($path, 'r.xlsx', null, null, true));

        $this->assertSame('Bolak balik', $items[0]['title']);
        $this->assertSame('Pendidikan', $items[0]['category']);
        $this->assertTrue($items[0]['is_published']);
    }
}
