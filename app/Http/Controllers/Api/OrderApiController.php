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
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            // 1) Validate incoming request
            $validated = $request->validate([
                'address' => ['required', 'string', 'max:500'],
                // Frontend sends "cod" or "card"
                'payment_method' => ['required', 'in:cod,card'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
                'items.*.size' => ['nullable', 'string', 'max:10'], // not stored for now
                'items.*.quantity' => ['required', 'integer', 'min:1'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $e->errors(),
            ], 422);
        }

        try {
            // 2) Use a transaction like in your old store() method
            $order = DB::transaction(function () use ($validated, $user) {
                $itemsData = $validated['items'];

                $total = 0;
                $orderItemsPayload = [];

                // Build items + calculate total (using product price from DB)
                foreach ($itemsData as $item) {
                    /** @var Product|null $product */
                    $product = Product::find($item['product_id']);

                    if (!$product) {
                        throw ValidationException::withMessages([
                            'items' => ['One of the selected products was not found.'],
                        ]);
                    }

                    $quantity  = $item['quantity'];
                    $unitPrice = $product->price;
                    $lineTotal = $unitPrice * $quantity;

                    $total += $lineTotal;

                    $orderItemsPayload[] = [
                        'product_id' => $product->id,
                        'quantity'   => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                        // If later you add a 'size' column on order_items, you can put it here
                        // 'size' => $item['size'] ?? null,
                    ];
                }

                // Map payment_method from API -> DB values
                // Old working controller used 'cash' or 'card'
                $paymentMethod = $validated['payment_method'] === 'cod'
                    ? 'cash'
                    : 'card';

                // 3) Create order (same style as old OrderController@store)
                /** @var Order $order */
                $order = Order::create([
                    'user_id'        => $user->id,
                    'total'          => $total,
                    'status'         => 'pending',              // matches old code
                    'payment_method' => $paymentMethod,         // 'cash' | 'card'
                    'address'        => $validated['address'],  // you already have this column
                ]);

                // 4) Create order_items
                foreach ($orderItemsPayload as $itemData) {
                    $itemData['order_id'] = $order->id;
                    OrderItem::create($itemData);
                }

                // 5) Load relations for response (like show page)
                $order->load(['items.product', 'user']);

                return $order;
            });

            return response()->json([
                'message' => 'Order placed successfully.',
                'order'   => $order,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            // Log once, but always send JSON to frontend
            \Log::error('Checkout API error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Server error during checkout.',
                // You can remove 'error' in production if you want
                'error'   => $e->getMessage(),
            ], 500);
        }
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
}
