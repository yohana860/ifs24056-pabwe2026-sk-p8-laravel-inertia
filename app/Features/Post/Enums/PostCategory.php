<?php

namespace App\Features\Post\Enums;

enum PostCategory: string
{
    case Teknologi = 'Teknologi';
    case Pendidikan = 'Pendidikan';
    case GayaHidup = 'Gaya Hidup';
    case Bisnis = 'Bisnis';
    case Opini = 'Opini';
    case Umum = 'Umum';

    /**
     * Deskripsi singkat kategori sesuai modul praktikum.
     */
    public function description(): string
    {
        return match ($this) {
            self::Teknologi => 'Teknologi & Koding',
            self::Pendidikan => 'Pendidikan & Tutorial',
            self::GayaHidup => 'Gaya Hidup & Produktivitas',
            self::Bisnis => 'Bisnis & Karir',
            self::Opini => 'Opini & Pemikiran',
            self::Umum => 'Umum',
        };
    }

    /**
     * Daftar nilai string seluruh kategori.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $category) => $category->value, self::cases());
    }

    /**
     * Opsi kategori untuk dropdown di frontend.
     *
     * @return list<array{value: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category) => ['value' => $category->value, 'description' => $category->description()],
            self::cases()
        );
    }
}
