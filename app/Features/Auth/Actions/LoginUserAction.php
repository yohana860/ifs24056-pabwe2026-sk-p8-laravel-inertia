<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    public function execute(
        string $login,
        string $password,
        bool $remember = false
    ): bool {
        $login = trim($login);

        $field = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        return Auth::guard('web')->attempt([
            $field => $login,
            'password' => $password,
        ], $remember);
    }
}
