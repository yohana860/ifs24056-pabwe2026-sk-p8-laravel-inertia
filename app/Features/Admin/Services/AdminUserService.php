<?php

namespace App\Features\Admin\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminUserService
{
    /**
     * Daftar pengguna beserta jumlah artikel (total & terbit).
     */
    public function paginateUsers(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return User::query()
            ->withCount([
                'posts',
                'posts as published_posts_count' => fn (Builder $q) => $q->where('is_published', true),
            ])
            ->when($term !== '', function (Builder $q) use ($term) {
                $like = '%'.mb_strtolower($term).'%';

                $q->where(function (Builder $w) use ($like) {
                    $w->whereRaw('LOWER(users.name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(users.username) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(users.email) LIKE ?', [$like]);
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
