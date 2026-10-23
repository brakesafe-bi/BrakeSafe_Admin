<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        return view('profile.index', ['profile' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = $request->user();
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'email' => ['required', 'email', 'max:50', Rule::unique('admins', 'email')->ignore($profile->getKey())],
            'current_password' => ['nullable', 'required_with:password,password_confirmation', 'current_password'],
            'password' => ['nullable', 'required_with:password_confirmation', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable', 'required_with:password', 'string'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $fullName = trim($validated['first_name'].' '.$validated['last_name']);
        $attributes = [
            'name' => $fullName,
            'full_name' => $fullName,
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $oldPhotoPath = $profile->profile_photo_path;
        $newPhotoPath = $request->file('profile_photo')?->storePublicly('profile-photos', 'public');
        if ($request->hasFile('profile_photo') && $newPhotoPath === false) {
            return back()
                ->withErrors(['profile_photo' => 'The profile picture could not be saved. Please try again.'])
                ->withInput($request->except('profile_photo'));
        }
        if (is_string($newPhotoPath)) {
            $attributes['profile_photo_path'] = $newPhotoPath;
        }

        $profile->update($attributes);

        if (is_string($newPhotoPath) && $oldPhotoPath && ! Storage::disk('public')->delete($oldPhotoPath)) {
            Log::warning("Unable to remove the administrator's previous profile picture.", [
                'admin_id' => $profile->getKey(),
                'profile_photo_path' => $oldPhotoPath,
            ]);
        }

        return redirect()->route('profile.edit')->with('status', 'Profile updated successfully.');
    }
}
