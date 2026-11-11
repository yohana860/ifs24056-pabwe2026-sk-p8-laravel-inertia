<?php

namespace Tests\Support;

use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * Pengganti UploadedFile::fake()->image() yang tidak bergantung pada ekstensi GD.
 *
 * UploadedFile::fake()->image() memanggil imagejpeg()/imagepng()/imagewebp(), yang
 * hanya ada jika GD dikompilasi dengan dukungan format tersebut. Helper ini memakai
 * berkas gambar 1x1 asli sehingga validasi `image` dan `mimes` tetap diuji dengan
 * isi berkas yang valid, tanpa GD.
 */
final class FakeImage
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGM4YWQEAALyAS2saifrAAAAAElFTkSuQmCC';

    private const JPEG = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDnqKKK8k/Qz//Z';

    private const WEBP = 'UklGRjoAAABXRUJQVlA4IC4AAACwAQCdASoBAAEAAUAmJaACdLoABDAAAP7vUS/xbSOhTIf/cHH/YOP+wcfumAAA';

    /**
     * Buat berkas gambar palsu (jpg/jpeg/png/webp) berdasarkan ekstensi nama berkas.
     */
    public static function make(string $name): File
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $encoded = match ($extension) {
            'jpg', 'jpeg' => self::JPEG,
            'png' => self::PNG,
            'webp' => self::WEBP,
            default => throw new InvalidArgumentException("Format gambar palsu tidak didukung: {$extension}"),
        };

        return UploadedFile::fake()->createWithContent($name, base64_decode($encoded, true));
    }
}
