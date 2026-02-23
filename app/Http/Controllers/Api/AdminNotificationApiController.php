<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminNotificationApiController extends Controller
{
    /**
     * Send a push notification to all registered mobile devices.
     * Only super_admin is allowed (enforced in route middleware + here).
     */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
        ]);

        $title = $request->input('title');
        $body  = $request->input('body');

        // Collect all unique Expo push tokens
        $tokens = DeviceToken::pluck('token')->filter(fn ($t) => strpos($t, 'ExponentPushToken') === 0)->values()->all();

        if (empty($tokens)) {
            return response()->json(['message' => 'No registered devices.', 'sent' => 0]);
        }

        // Expo push API accepts up to 100 messages per request
        $chunks   = array_chunk($tokens, 100);
        $totalSent = 0;
        $errors    = [];

        foreach ($chunks as $chunk) {
            $messages = array_map(fn ($token) => [
                'to'    => $token,
                'title' => $title,
                'body'  => $body,
                'sound' => 'default',
            ], $chunk);

            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/push/send', $messages);

            if ($response->successful()) {
                $data = $response->json('data', []);
                foreach ($data as $result) {
                    if (($result['status'] ?? '') === 'ok') {
                        $totalSent++;
                    } else {
                        $errors[] = $result['message'] ?? 'Unknown error';
                    }
                }
            } else {
                $errors[] = 'Expo API error: ' . $response->status();
            }
        }

        return response()->json([
            'message' => "Notification sent to {$totalSent} device(s).",
            'sent'    => $totalSent,
            'errors'  => $errors,
        ]);
    }
}
