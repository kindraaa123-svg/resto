<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Find existing user by google_id or email
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if (! $user) {
                return redirect()->route('login')->withErrors(['email' => 'No staff account found for this Google email.']);
            }

            // Link account if not already linked
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->id]);
            }

            Auth::login($user);

            return redirect()->intended('/');
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Unable to login using Google. Please try again.']);
        }
    }
}
