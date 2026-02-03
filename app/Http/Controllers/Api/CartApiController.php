<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartApiController extends Controller
{
    /**
     * GET /api/cart
     * Returns the current user's cart (creates an empty one if missing).
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        $cart->load(['items.product.category']);

        return response()->json($cart);
    }

    /**
     * POST /api/cart/items
     * Add or update an item in the cart.
     *
     * body: { product_id, size?, quantity }
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'size'       => ['nullable', 'string', 'max:50'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $size = $data['size'] ?? null;

        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('size', $size)
            ->first();

        if ($item) {
            // Treat quantity as "set to this value"
            $item->quantity   = $data['quantity'];
            $item->unit_price = $product->price;
            $item->save();
        } else {
            $item = CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $product->id,
                'size'       => $size,
                'quantity'   => $data['quantity'],
                'unit_price' => $product->price,
            ]);
        }

        $cart->load(['items.product.category']);

        return response()->json($cart);
    }

    /**
     * DELETE /api/cart/items/{item}
     * Remove a single line from the cart by cart_item id.
     * (You can keep this if you ever need it, but the React app
     * will use destroyByProduct below instead.)
     */
    public function destroy(Request $request, CartItem $item)
    {
        $user = $request->user();

        if (!$item->cart || $item->cart->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $item->delete();

        return response()->json(['message' => 'Item removed']);
    }

    /**
     * DELETE /api/cart/clear
     * Empty the current user's cart.
     */
    public function clear(Request $request)
    {
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json(['message' => 'Cart cleared']);
    }

    /**
     * DELETE /api/cart/items
     * Remove a line by product_id + size (matches the React usage).
     *
     * body: { product_id, size? }
     */
    public function destroyByProduct(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'size'       => ['nullable', 'string', 'max:50'],
        ]);

        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Nothing to remove'], 200);
        }

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $data['product_id'])
            ->where('size', $data['size'] ?? null)
            ->first();

        if ($item) {
            $item->delete();
        }

        return response()->json(['message' => 'Item removed']);
    }
}
