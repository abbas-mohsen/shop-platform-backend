<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewApiController extends Controller
{
    /**
     * GET /api/products/{product}/reviews
     * List all reviews for a product (paginated, 10 per page).
     */
    public function index(Product $product, Request $request)
    {
        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * POST /api/products/{product}/reviews
     * Create a review (authenticated).
     */
    public function store(Request $request, Product $product)
    {
        $user = $request->user();

        // Check if user already reviewed this product
        $existing = Review::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'You have already reviewed this product.',
            ], 422);
        }

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = Review::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'rating'     => $data['rating'],
            'comment'    => $data['comment'] ?? null,
        ]);

        $review->load('user:id,name');

        return response()->json($review, 201);
    }

    /**
     * DELETE /api/reviews/{review}
     * Delete own review, or admin can delete any.
     */
    public function destroy(Request $request, Review $review)
    {
        $user = $request->user();

        if ($review->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'message' => 'You are not authorized to delete this review.',
            ], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }
}
