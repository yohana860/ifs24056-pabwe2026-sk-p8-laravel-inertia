<?php

namespace App\Features\Auth\Actions;

use App\Models\User;

class RegisterUserAction
{
    /**
     * Daftarkan pengguna baru. Role SELALU member (tidak bisa ditentukan dari input).
     *
     * @param  array{name: string, username: string, email: string, password: string}  $data
     */
    public function execute(array $data): User
    {
        $user = new User($data);
        $user->role = User::ROLE_MEMBER;
        $user->save();

        return $user;
    }
}
