<?php

namespace App\Features\Post\Policies;

use App\Features\Post\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Hanya pemilik artikel yang boleh membaca, mengubah, menghapus,
     * mengatur publikasi, dan mengelola cover artikelnya.
     */
    public function manage(User $user, Post $post): bool
    {
        return (int) $user->id === (int) $post->user_id;
    }
}
