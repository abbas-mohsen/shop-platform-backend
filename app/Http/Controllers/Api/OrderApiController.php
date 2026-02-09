<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class OrderApiController extends Controller
{
    /**
     * POST /api/checkout
     *
     * Expected JSON payload (from React):
     *
     * {
     *   "address": "string",
     *   "payment_method": "cod" | "card",
     *   "items": [
     *     { "product_id": 1, "size": "L", "quantity": 2 },
     *     ...
     *   ]
     * }
     *
     * NOTE:
     * - We validate "cod" or "card" from frontend.
     * - For the DB we map:
     *      "cod"  -> "cash"
     *      "card" -> "card"
     *   to match your old working OrderController (where payment_method was "cash" or "card").
     */
    public function checkout(Request $request)
{
    $user = $request->user(); // Sanctum user

    // 1) Validate payload
    $validated = $request->validate([
        'address'         => ['required', 'string', 'max:500'],
        'payment_method'  => ['required', 'in:cod,card'],
        'items'           => ['required', 'array', 'min:1'],
        'items.*.product_id' => ['required', 'exists:products,id'],
        'items.*.quantity'   => ['required', 'integer', 'min:1'],
        'items.*.size'       => ['nullable', 'string', 'max:20'],
    ]);

    $items = $validated['items'];

    return DB::transaction(function () use ($validated, $items, $user) {
        $orderTotal   = 0;
        $preparedItems = [];

        // 2) Validate stock and calculate totals
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $qty       = (int) $item['quantity'];
            $size      = $item['size'] ?? null;

            /** @var Product $product */
            $product = Product::lockForUpdate()->findOrFail($productId);

            $sizesStock = $product->sizes_stock ?? [];
            $perSizeAvailable = null;

            // If a size is specified and exists in sizes_stock, use that
            if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
                $perSizeAvailable = (int) $sizesStock[$size];

                if ($perSizeAvailable < $qty) {
                    return response()->json([
                        'message' => 'Not enough stock for this size.',
                        'error'   => "Not enough stock for size {$size} of product {$product->name}",
                    ], 422);
                }
            } else {
                // Fall back to global stock if set
                if (!is_null($product->stock) && $product->stock < $qty) {
                    return response()->json([
                        'message' => 'Not enough stock for this product.',
                        'error'   => "Not enough stock for product {$product->name}",
                    ], 422);
                }
            }

            $unitPrice = $product->price;
            $lineTotal = $unitPrice * $qty;
            $orderTotal += $lineTotal;

            $preparedItems[] = [
                'product'    => $product,
                'product_id' => $product->id,
                'size'       => $size,
                'quantity'   => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        // 3) Create order
        // Map payment_method: 'cod' -> 'cash', 'card' -> 'card' (as per DB schema)
        $paymentMethod = $validated['payment_method'] === 'cod' ? 'cash' : 'card';
        
        $order = Order::create([
            'user_id'        => $user ? $user->id : null,
            'payment_method' => $paymentMethod,
            'status'         => 'pending',
            'total'          => $orderTotal,
            // ⚠️ Do NOT add 'address' here unless you actually have an 'address' column.
            // If you later add an 'address' column, you can include it like:
            // 'address' => $validated['address'],
        ]);

        // 4) Create order_items and update product stock
        foreach ($preparedItems as $itemData) {
            /** @var Product $product */
            $product  = $itemData['product'];
            $size     = $itemData['size'];
            $qty      = $itemData['quantity'];

            // Create order item (if you added a 'size' column in order_items, include it here)
            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'quantity'   => $qty,
                'unit_price' => $itemData['unit_price'],
                'line_total' => $itemData['line_total'],
                // 'size'    => $size, // uncomment if your table has this column
            ]);

            // Update per-size stock
            $sizesStock = $product->sizes_stock ?? [];
            if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
                $sizesStock[$size] = max(0, (int) $sizesStock[$size] - $qty);
                $product->sizes_stock = $sizesStock;
            }

            // Also update global stock if you are still using it
            if (!is_null($product->stock)) {
                $product->stock = max(0, (int) $product->stock - $qty);
            }

            $product->save();
        }

        // 5) Return the order with items + products for frontend
        $order->load(['items.product']);

        return response()->json([
            'message' => 'Order placed successfully.',
            'order'   => $order,
        ], 201);
    });
}

     public function myOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with(['items.product']) // eager load items + product
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    /**
     * GET /api/my-orders/{order}
     * Return a single order for the authenticated user.
     */
    public function showMyOrder(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Make sure the order belongs to the current user
        if ($order->user_id !== $user->id) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        $order->loadMissing(['items.product', 'user']);

        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * PUT /api/orders/{order}/cancel
     * Cancel a pending order (owner only).
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Only the owner can cancel
        if ($order->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to cancel this order.',
            ], 403);
        }

        // Only pending orders can be cancelled
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled.',
            ], 422);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json($order->fresh());
    }
}
