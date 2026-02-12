<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderApiController extends Controller
{
    private CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    /**
     * POST /api/checkout
     * Uses CheckoutService for all business logic (stock validation, order creation, inventory deduction).
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user      = $request->user();
        $validated = $request->validated();

        try {
            $order = $this->checkoutService->execute(
                $user->id,
                $validated['items'],
                $validated['payment_method'],
                $validated['address']
            );

            return response()->json([
                'message' => 'Order placed successfully.',
                'order'   => new OrderResource($order),
            ], 201);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => 'Not enough stock.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/my-orders
     */
    public function myOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with(['items.product'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'orders' => OrderResource::collection($orders),
        ]);
    }

    /**
     * GET /api/my-orders/{order}
     * Uses Policy for authorization.
     */
    public function showMyOrder(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->loadMissing(['items.product', 'user']);

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * PUT /api/orders/{order}/cancel
     * Uses Policy for authorization.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled.',
            ], 422);
        }

        $oldStatus = $order->status;
        $order->update(['status' => 'cancelled']);

        // Send cancellation email
        try {
            $user = $request->user();
            if ($user && $user->email) {
                Mail::to($user->email)->queue(new OrderStatusUpdated($order->fresh(), $oldStatus));
            }
        } catch (\Exception $e) {
            \Log::warning('Order cancellation email failed: ' . $e->getMessage());
        }

        return response()->json(new OrderResource($order->fresh()));
    }
}
