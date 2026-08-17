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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
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
            // Security event. Logged with the source IP so repeated failures can
            // be traced after the fact; the response stays deliberately generic
            // so it still reveals nothing about whether the email exists.
            Log::warning('auth.login_failed', [
                'email' => $data['email'],
                'ip'    => $request->ip(),
            ]);

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

        // Evict every other session. If someone changes their password because
        // the account was compromised, leaving the attacker's existing token
        // valid defeats the entire point of the change. The token used for this
        // request is kept so the user is not logged out of their own device.
        //
        // currentAccessToken() is a TransientToken under actingAs() in tests and
        // has no id, so fall back to revoking everything rather than crashing.
        $current   = $request->user()->currentAccessToken();
        $currentId = $current instanceof \Laravel\Sanctum\PersonalAccessToken ? $current->id : null;

        $tokens = $user->tokens();
        if ($currentId !== null) {
            $tokens->where('id', '!=', $currentId);
        }
        $tokens->delete();

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
        // currentAccessToken() can be a TransientToken (cookie/session auth or
        // tests using actingAs), which has no delete() — guard to avoid a 500.
        $token = $request->user()->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }
}
