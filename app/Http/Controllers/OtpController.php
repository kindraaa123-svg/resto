<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class OtpController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/OtpLogin');
    }

    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'No account found with this email address.',
        ]);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 5 minutes
        Cache::put('otp_'.$request->email, $otp, now()->addMinutes(5));

        // Send Email
        try {
            Mail::to($request->email)->send(new OtpMail($otp));

            return back()->with('success', 'A security code has been sent to your email.');
        } catch (\Exception $e) {
            Log::error('OTP Email Error: '.$e->getMessage());

            return back()->withErrors(['email' => 'Failed to send security code. Please try again.']);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        $cachedOtp = Cache::get('otp_'.$request->email);

        if ($cachedOtp && $cachedOtp === $request->otp) {
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return back()->withErrors(['otp' => 'Account no longer exists.']);
            }

            Auth::login($user);
            Cache::forget('otp_'.$request->email);

            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        return back()->withErrors(['otp' => 'The security code you entered is invalid or has expired.']);
    }
}
