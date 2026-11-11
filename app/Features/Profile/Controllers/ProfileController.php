<?php

namespace App\Features\Profile\Controllers;

use App\Features\Post\Services\PostService;
use App\Features\Profile\Requests\UpdateProfileRequest;
use App\Features\Profile\Services\ProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request, PostService $posts): Response
    {
        return Inertia::render('Profile/Edit', [
            'stats' => $posts->statsForUser($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request, ProfileService $profile): RedirectResponse
    {
        $profile->update($request->user(), $request->validated(), $request->file('photo'));

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }
}
