<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewApiController extends Controller
{
    /**
     * GET /api/products/{product}/reviews
     */
    public function index(Product $product, Request $request)
    {
        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    /**
     * POST /api/products/{product}/reviews
     */
    public function store(StoreReviewRequest $request, Product $product)
    {
        $user = $request->user();

        $existing = Review::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'You have already reviewed this product.',
            ], 422);
        }

        $data = $request->validated();

        $review = Review::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'rating'     => $data['rating'],
            'comment'    => $data['comment'] ?? null,
        ]);

        $review->load('user:id,name');

        return new ReviewResource($review);
    }

    /**
     * DELETE /api/reviews/{review}
     * Uses ReviewPolicy for authorization.
     */
    public function destroy(Request $request, Review $review)
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }
}
