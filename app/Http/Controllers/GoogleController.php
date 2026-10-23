<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $user = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['google' => 'Google authentication failed.']);
        }

        $admin = Admin::where('google_id', $user->id)->orWhere('email', $user->email)->first();

        if ($admin) {
            $admin->update([
                'google_id' => $user->id,
                'is_verified' => true,
            ]);
        } else {
            $adminCount = Admin::count();
            if ($adminCount >= 2) {
                return redirect('/login')->withErrors(['google' => 'Admin slots full.']);
            }

            $admin = Admin::create([
                'name' => $user->name,
                'full_name' => $user->name,
                'email' => $user->email,
                'google_id' => $user->id,
                'is_verified' => true,
                'password' => bcrypt(uniqid()),
            ]);
        }

        Auth::login($admin);
        request()->session()->regenerate();

        return redirect('/')->with('login_success', true);
    }
}
