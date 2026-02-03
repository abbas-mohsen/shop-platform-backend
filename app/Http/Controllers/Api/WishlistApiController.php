<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class WishlistApiController extends Controller
{
    /**
     * GET /api/wishlist
     * Returns current user's wishlist items with product + category.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $items = WishlistItem::with(['product.category'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($items);
    }

    /**
     * POST /api/wishlist
     * body: { product_id }
     * Adds a product to wishlist if not already present.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $productId = $data['product_id'];

        // Avoid duplicate row thanks to unique index, but also check manually
        WishlistItem::firstOrCreate(
            [
                'user_id'    => $user->id,
                'product_id' => $productId,
            ],
            []
        );

        $item = WishlistItem::with(['product.category'])
            ->where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        return response()->json($item, 201);
    }

    /**
     * DELETE /api/wishlist/{product}
     * Removes a product from the wishlist by product id.
     */
    public function destroy(Request $request, Product $product)
    {
        $user = $request->user();

        WishlistItem::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->delete();

        return response()->json(['message' => 'Removed from wishlist']);
    }
}
