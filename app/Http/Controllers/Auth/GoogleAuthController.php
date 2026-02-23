<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        // IMPORTANT: stateless here as well
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    public function callback()
    {
        try {
            // This is where the error happens right now
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            // Log for Laravel, but ALSO send the real message to React so we can see it
            Log::error('Google OAuth error', [
                'message' => $e->getMessage(),
            ]);

            $frontend = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

            // NOTE: we now send the reason in the URL for debugging
            return redirect($frontend . '/login?error=google&reason=' . urlencode($e->getMessage()));
        }

        // Find or create user
        $user = User::where('google_id', $googleUser->getId())
                    ->orWhere('email', $googleUser->getEmail())
                    ->first();

        if (! $user) {
            $user = User::create([
                'name'      => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User',
                'email'     => $googleUser->getEmail(),
                'password'  => Hash::make(uniqid('google_', true)),
                'google_id' => $googleUser->getId(),
                'role'      => 'customer',
            ]);
        } elseif (! $user->google_id) {
            // Existing email-based account — link the Google ID
            $user->update(['google_id' => $googleUser->getId()]);
        }

        // Sanctum token
        $token = $user->createToken('google-login')->plainTextToken;

        $frontend = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        return redirect($frontend . '/auth/google/callback?token=' . urlencode($token));
    }
}
