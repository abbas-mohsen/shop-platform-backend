<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    // Redirect the user to Google's OAuth page
    public function redirect()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    // Handle the callback from Google
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            // you can log it if you want
            return redirect(config('app.frontend_url', env('FRONTEND_URL')) . '/login?error=google');
        }

        // Find or create local user
        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name'     => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User',
                'email'    => $googleUser->getEmail(),
                // random password, user will always use Google to sign in
                'password' => Hash::make(uniqid('google_', true)),
                'is_admin' => 0,
                'role'     => 'customer',
            ]);
        }

        // Create Sanctum token
        $token = $user->createToken('google-login')->plainTextToken;

        // Redirect back to React with token in query string
        $frontend = config('app.frontend_url', env('FRONTEND_URL'));

        return redirect($frontend . '/auth/google/callback?token=' . urlencode($token));
    }
}
