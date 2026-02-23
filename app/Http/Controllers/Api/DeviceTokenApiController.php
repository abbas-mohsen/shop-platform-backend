<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenApiController extends Controller
{
    /**
     * Save or update the Expo push token for the authenticated user.
     * If the same token already belongs to this user, it's a no-op.
     * If the token previously belonged to another user (e.g. shared device), it is reassigned.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string',
            'platform' => 'nullable|in:ios,android',
        ]);

        $token    = $request->input('token');
        $platform = $request->input('platform');
        $user     = $request->user();

        // Upsert: update if token exists, otherwise create
        DeviceToken::updateOrCreate(
            ['token' => $token],
            ['user_id' => $user->id, 'platform' => $platform]
        );

        return response()->json(['message' => 'Device token saved.']);
    }

    /**
     * Remove the device token on logout so the user stops receiving notifications.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        DeviceToken::where('token', $request->input('token'))
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Device token removed.']);
    }
}
