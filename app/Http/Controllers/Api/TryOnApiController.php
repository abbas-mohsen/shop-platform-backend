<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\TryOnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TryOnApiController extends Controller
{
    private TryOnService $tryOnService;

    public function __construct(TryOnService $tryOnService)
    {
        $this->tryOnService = $tryOnService;
    }

    public function generate(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        // Garment image — read from local storage as base64 so FASHN's servers
        // never need to reach our localhost URL (which they can't, in dev).
        if (!$product->image) {
            return response()->json(['message' => 'This product has no image to try on.'], 422);
        }

        $garmentPath = storage_path('app/public/' . $product->image);
        if (!is_file($garmentPath)) {
            return response()->json(['message' => 'Product image not found on disk.'], 422);
        }

        // Customer photo — read directly into memory as base64.
        // We intentionally NEVER call ->store() on this file.
        // PHP automatically deletes its own upload temp file at end of request.
        $photo       = $request->file('photo');
        $modelMime   = $photo->getMimeType() ?: 'image/jpeg';
        $modelBase64 = 'data:' . $modelMime . ';base64,' . base64_encode(
            file_get_contents($photo->getRealPath())
        );

        $garmentMime   = mime_content_type($garmentPath) ?: 'image/jpeg';
        $garmentBase64 = 'data:' . $garmentMime . ';base64,' . base64_encode(
            file_get_contents($garmentPath)
        );

        try {
            $resultUrl = $this->tryOnService->generate($modelBase64, $garmentBase64);

            return response()->json(['result_image' => $resultUrl]);
        } catch (\Throwable $e) {
            Log::error('Try-on generation failed', [
                'product_id' => $product->id,
                'user_id'    => $request->user()->id,
                'error'      => $e->getMessage(),
                'line'       => $e->getFile() . ':' . $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Try-on is temporarily unavailable. Please try again later.',
            ], 500);
        }
    }
}
