<?php

namespace App\Features\Post\Models;

use App\Features\Post\Enums\PostCategory;
use App\Models\User;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * user_id sengaja tidak mass-assignable; pemilik artikel diisi eksplisit oleh repository.
 */
#[Fillable([
    'title',
    'slug',
    'category',
    'excerpt',
    'content',
    'cover_image_path',
    'is_published',
    'published_at',
])]
#[UseFactory(PostFactory::class)]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * Atribut tambahan yang ikut diserialisasi ke frontend.
     *
     * @var list<string>
     */
    protected $appends = ['cover_url'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'category' => PostCategory::class,
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * URL publik cover artikel (null jika tidak ada cover).
     *
     * @return Attribute<string|null, never>
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->cover_image_path
                ? Storage::disk('public')->url($this->cover_image_path)
                : null
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
