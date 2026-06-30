<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TryOnService
{
    private const SUBMIT_URL = 'https://api.fashn.ai/v1/run';
    private const STATUS_URL = 'https://api.fashn.ai/v1/status/';

    public function generate(string $modelImageBase64, string $garmentImageBase64): string
    {
        $apiKey = config('services.fashn.key');

        if (!$apiKey) {
            throw new \RuntimeException('Try-on is not configured yet. Add FASHN_API_KEY to .env.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(20)->post(self::SUBMIT_URL, [
            'model_name' => 'tryon-v1.6',
            'inputs' => [
                'model_image'   => $modelImageBase64,
                'garment_image' => $garmentImageBase64,
                'mode'          => 'performance',
                'return_base64' => false,
            ],
        ]);

        Log::info('FASHN submit response', ['status' => $response->status(), 'body' => $response->body()]);

        if ($response->failed()) {
            Log::error('FASHN submit failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Failed to submit try-on request.');
        }

        $predictionId = $response->json('id');

        Log::info('FASHN prediction ID', ['id' => $predictionId, 'type' => gettype($predictionId)]);

        if (!$predictionId) {
            throw new \RuntimeException('No prediction ID returned from try-on API.');
        }

        return $this->poll($predictionId, $apiKey);
    }

    private function poll(string $predictionId, string $apiKey): string
    {
        $maxAttempts = 24; // 24 × 1.5s = 36s max wait
        $attempt     = 0;

        while ($attempt < $maxAttempts) {
            usleep(1500000); // 1.5s

            $status = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(10)->get(self::STATUS_URL . $predictionId);

            if ($status->failed()) {
                throw new \RuntimeException('Failed to check try-on status.');
            }

            $body = $status->json();

            if (($body['status'] ?? '') === 'completed') {
                $output = $body['output'][0] ?? null;
                if (!$output) {
                    throw new \RuntimeException('Try-on completed but no output image returned.');
                }
                return $output;
            }

            if (($body['status'] ?? '') === 'failed') {
                $errDetail = $body['error'] ?? [];
                $errName   = is_array($errDetail) ? ($errDetail['name'] ?? '') : '';

                if ($errName === 'PoseError') {
                    throw new \RuntimeException(
                        'Could not detect your body pose. Please use a photo where you are standing upright, facing the camera, with your full upper body visible.'
                    );
                }

                $errMsg = is_array($errDetail) ? json_encode($errDetail) : (string) $errDetail;
                throw new \RuntimeException('Try-on generation failed: ' . $errMsg);
            }

            $attempt++;
        }

        throw new \RuntimeException('Try-on generation timed out after 36 seconds.');
    }
}
