<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminOrderApiController extends Controller
{
    private CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function index()
    {
        $orders = Order::with(['user', 'items.product'])
            ->latest()
            ->paginate(25);

        return OrderResource::collection($orders);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('updateStatus', $order);

        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,delivered,cancelled'],
        ]);

        $oldStatus = $order->status;

        $order->status = $data['status'];
        $order->save();

        // Restore stock when an order is cancelled via status change
        if ($data['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
            $this->checkoutService->restoreStock($order);
        }

        // Send status update email
        if ($oldStatus !== $order->status) {
            try {
                $order->loadMissing('user');
                if ($order->user && $order->user->email) {
                    Mail::to($order->user->email)->queue(new OrderStatusUpdated($order, $oldStatus));
                }
            } catch (\Exception $e) {
                \Log::warning('Order status email failed: ' . $e->getMessage());
            }
        }

        return new OrderResource($order);
    }

    public function cancel(Order $order)
    {
        $this->authorize('cancel', $order);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled.',
            ], 422);
        }

        $order->update(['status' => 'cancelled']);

        // Restore stock now that the order is cancelled
        $this->checkoutService->restoreStock($order);

        // Send cancellation email
        try {
            $order->loadMissing('user');
            if ($order->user && $order->user->email) {
                Mail::to($order->user->email)->queue(new OrderStatusUpdated($order->fresh(), 'pending'));
            }
        } catch (\Exception $e) {
            \Log::warning('Order cancellation email failed: ' . $e->getMessage());
        }

        return new OrderResource($order->fresh());
    }
}
