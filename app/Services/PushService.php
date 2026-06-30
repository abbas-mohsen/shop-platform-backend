<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushService
{
    /**
     * Send a push notification to all devices belonging to a specific user.
     * Fires-and-forgets — errors are logged but never re-thrown.
     */
    public function notifyUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('token')
            ->filter(fn ($t) => strpos($t, 'ExponentPushToken') === 0)
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        $messages = array_map(fn ($token) => [
            'to'    => $token,
            'title' => $title,
            'body'  => $body,
            'sound' => 'default',
            'data'  => $data,
        ], $tokens);

        try {
            Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/push/send', $messages);
        } catch (\Exception $e) {
            Log::warning('Push notification failed for user ' . $userId . ': ' . $e->getMessage());
        }
    }
}
