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

    /**
     * GET /api/admin/orders
     *
     * Query params:
     *  - status: pending | approved | rejected | delivered | cancelled
     *  - search: order id, or customer name / email / phone (partial)
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product'])->latest();

        $status = $request->query('status');
        if ($status && in_array($status, ['pending', 'approved', 'rejected', 'delivered', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
                $q->orWhereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        $orders = $query->paginate(25)->appends($request->query());

        return OrderResource::collection($orders);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('updateStatus', $order);

        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,delivered,cancelled'],
        ]);

        $oldStatus = $order->status;

        // Cancelled is terminal: its stock has already been restored, so
        // re-activating would sell units that were never re-deducted, and a
        // later re-cancel would restore stock a second time (inventory drift).
        if ($oldStatus === 'cancelled' && $data['status'] !== 'cancelled') {
            return response()->json([
                'message' => 'Cancelled orders cannot be reactivated. Ask the customer to place a new order.',
            ], 422);
        }

        $order->status = $data['status'];
        $order->save();

        $this->syncStockForTransition($order, $oldStatus, $data['status']);

        // Send status update email
        if ($oldStatus !== $order->status) {
            try {
                $order->loadMissing('user');
                if ($order->user && $order->user->email) {
                    Mail::to($order->user->email)->send(new OrderStatusUpdated($order, $oldStatus));
                }
            } catch (\Exception $e) {
                \Log::warning('Order status email failed: ' . $e->getMessage());
            }
        }

        return new OrderResource($order);
    }

    /**
     * Apply one status to several orders in a single request. Looping here
     * avoids the dropped/raced requests that firing many parallel PUTs can
     * cause. Orders that can't transition (unauthorized, terminal cancel, or
     * already at the target) are skipped, mirroring updateStatus().
     */
    public function bulkUpdateStatus(Request $request)
    {
        $data = $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:orders,id'],
            'status' => ['required', 'in:pending,approved,rejected,delivered,cancelled'],
        ]);

        $orders  = Order::whereIn('id', $data['ids'])->get();
        $updated = 0;

        foreach ($orders as $order) {
            if (! $request->user()->can('updateStatus', $order)) {
                continue;
            }

            $oldStatus = $order->status;
            // Cancelled is terminal, and skip no-op changes.
            if (($oldStatus === 'cancelled' && $data['status'] !== 'cancelled')
                || $oldStatus === $data['status']) {
                continue;
            }

            $order->status = $data['status'];
            $order->save();

            $this->syncStockForTransition($order, $oldStatus, $data['status']);

            try {
                $order->loadMissing('user');
                if ($order->user && $order->user->email) {
                    Mail::to($order->user->email)->send(new OrderStatusUpdated($order, $oldStatus));
                }
            } catch (\Exception $e) {
                \Log::warning('Order status email failed: ' . $e->getMessage());
            }

            $updated++;
        }

        return response()->json([
            'message'       => "{$updated} order(s) updated.",
            'updated_count' => $updated,
        ]);
    }

    /**
     * Keep inventory in sync with the order's lifecycle. Rejected and
     * cancelled orders don't consume stock, so their units are restored;
     * re-activating a rejected order takes the units back out.
     * (Cancelled is terminal, so only rejected can ever leave this set.)
     */
    private function syncStockForTransition(Order $order, string $oldStatus, string $newStatus): void
    {
        $restocked = ['rejected', 'cancelled'];
        $was  = in_array($oldStatus, $restocked, true);
        $will = in_array($newStatus, $restocked, true);

        if (! $was && $will) {
            $this->checkoutService->restoreStock($order);
        } elseif ($was && ! $will) {
            $this->checkoutService->reapplyStock($order);
        }
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
                Mail::to($order->user->email)->send(new OrderStatusUpdated($order->fresh(), 'pending'));
            }
        } catch (\Exception $e) {
            \Log::warning('Order cancellation email failed: ' . $e->getMessage());
        }

        return new OrderResource($order->fresh());
    }
}
