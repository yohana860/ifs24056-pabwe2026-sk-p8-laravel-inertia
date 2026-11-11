<?php

namespace App\Features\Post\Services;

use App\Features\Post\Enums\PostCategory;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PostExcelService
{
    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /** Nilai kolom Status yang dianggap "terbit" saat impor. */
    private const PUBLISHED_LABELS = ['published', 'terbit', 'dipublikasikan'];

    /**
     * Bangun file .xlsx dan kembalikan sebagai response unduhan.
     */
    public function download(iterable $posts, string $filename, bool $withAuthor = false): Response
    {
        return new Response($this->export($posts, $withAuthor), 200, [
            'Content-Type' => self::XLSX_MIME,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Ekspor artikel ke isi biner file .xlsx.
     * Setiap sel ditulis sebagai string eksplisit agar teks berawalan "=" tidak dieksekusi sebagai formula.
     */
    public function export(iterable $posts, bool $withAuthor = false): string
    {
        $headers = ['Judul', 'Kategori', 'Ringkasan', 'Isi', 'Status', 'Dipublikasikan', 'Dibuat'];

        if ($withAuthor) {
            $headers[] = 'Penulis';
        }

        $rows = Collection::make($posts)->map(function ($post) use ($withAuthor) {
            $row = [
                $post->title,
                $post->category->value,
                (string) $post->excerpt,
                $post->content,
                $post->is_published ? 'Published' : 'Draft',
                $post->published_at?->format('Y-m-d H:i:s') ?? '',
                $post->created_at->format('Y-m-d H:i:s'),
            ];

            if ($withAuthor) {
                $row[] = $post->user->name;
            }

            return $row;
        });

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Artikel');

        foreach ($rows->prepend($headers)->values() as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $sheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 1], (string) $value, DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'posts_xlsx_');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        $binary = (string) file_get_contents($path);
        unlink($path);

        return $binary;
    }

    /**
     * Baca file Excel dan ubah baris data menjadi payload artikel.
     * Kolom: A Judul, B Kategori, C Ringkasan, D Isi, E Status. Baris pertama = header.
     * Baris tanpa judul/isi dilewati; kategori tidak dikenal menjadi "Umum".
     *
     * @return list<array{title: string, category: string, excerpt: string|null, content: string, is_published: bool}>
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parse(UploadedFile $file): array
    {
        $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, false);

        $items = [];

        foreach (array_slice($rows, 1) as $row) {
            $title = trim((string) ($row[0] ?? ''));
            $content = trim((string) ($row[3] ?? ''));

            if ($title === '' || $content === '') {
                continue;
            }

            $excerpt = trim((string) ($row[2] ?? ''));

            $items[] = [
                'title' => mb_substr($title, 0, 255),
                'category' => (PostCategory::tryFrom(trim((string) ($row[1] ?? ''))) ?? PostCategory::Umum)->value,
                'excerpt' => $excerpt === '' ? null : $excerpt,
                'content' => $content,
                'is_published' => in_array(mb_strtolower(trim((string) ($row[4] ?? ''))), self::PUBLISHED_LABELS, true),
            ];
        }

        return $items;
    }
}
