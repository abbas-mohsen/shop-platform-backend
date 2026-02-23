<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * SECURITY: Role is always forced to 'customer'. Admin accounts
     * must be created via the database, a seeder, or a protected admin endpoint.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role'     => 'customer',   // Always force customer role
        ]);

        $token = $user->createToken('frontend')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Delete old tokens to prevent token accumulation
        $user->tokens()->delete();

        $token = $user->createToken('frontend')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        // Always return success to avoid revealing whether the email is registered
        return response()->json([
            'message' => 'If that email is registered, you will receive a password reset link shortly.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'                 => 'required|string',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete(); // Invalidate all existing sessions
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully.']);
        }

        return response()->json(['message' => __($status)], 422);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Sign in or register via Google OAuth.
     * Receives an access_token from the mobile app, validates it with Google,
     * then finds or creates the matching user and returns a Sanctum token.
     */
    public function googleAuth(Request $request): JsonResponse
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        $googleResponse = Http::get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'access_token' => $request->input('access_token'),
        ]);

        if (! $googleResponse->successful()) {
            return response()->json(['message' => 'Invalid Google token.'], 401);
        }

        $googleUser = $googleResponse->json();

        if (empty($googleUser['email'])) {
            return response()->json(['message' => 'Could not retrieve email from Google.'], 422);
        }

        $user = User::firstOrCreate(
            ['email' => $googleUser['email']],
            [
                'name'              => $googleUser['name'] ?? explode('@', $googleUser['email'])[0],
                'password'          => Hash::make(Str::random(32)),
                'role'              => 'customer',
                'email_verified_at' => now(),
            ]
        );

        // Revoke old tokens to prevent accumulation
        $user->tokens()->delete();

        $token = $user->createToken('frontend')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }
}
