<?php

namespace App\Features\Auth\Controllers;

use App\Features\Auth\Actions\LoginUserAction;
use App\Features\Auth\Actions\RegisterUserAction;
use App\Features\Auth\Requests\LoginRequest;
use App\Features\Auth\Requests\RegisterRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(
        LoginRequest $request,
        LoginUserAction $action
    ): RedirectResponse {
        $credentials = $request->validated();

        $success = $action->execute(
            $credentials['login'],
            $credentials['password'],
            $request->boolean('remember')
        );

        if (! $success) {
            throw ValidationException::withMessages([
                'login' => 'Username/email atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        $destination = $user->isAdmin()
            ? route('admin.users.index')
            : route('posts.index');

        return redirect()->intended($destination);
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(
        RegisterRequest $request,
        RegisterUserAction $action
    ): RedirectResponse {
        $user = $action->execute($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('posts.index')
            ->with('success', 'Akun berhasil dibuat. Selamat menulis!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
