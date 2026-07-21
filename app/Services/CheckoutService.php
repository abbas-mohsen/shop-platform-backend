<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function execute(int $userId, array $items, string $paymentMethod, string $address, ?string $couponCode = null, ?float $latitude = null, ?float $longitude = null): Order
    {
        $order = DB::transaction(function () use ($userId, $items, $paymentMethod, $address, $couponCode, $latitude, $longitude) {
            $orderTotal    = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty     = (int) $item['quantity'];
                $size    = $item['size'] ?? null;
                $color   = $item['color'] ?? null;

                $this->validateStock($product, $qty, $size);

                $unitPrice = $product->price;
                $lineTotal = $unitPrice * $qty;
                $orderTotal += $lineTotal;

                $preparedItems[] = [
                    'product'    => $product,
                    'product_id' => $product->id,
                    'size'       => $size,
                    'color'      => $color,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $discountAmount  = 0;
            $appliedCoupon   = null;

            if ($couponCode) {
                $coupon = Coupon::lockForUpdate()
                    ->where('code', strtoupper(trim($couponCode)))
                    ->first();

                if ($coupon && $coupon->isValidFor($orderTotal)) {
                    $discountAmount = $coupon->calculateDiscount($orderTotal);
                    $coupon->increment('used_count');
                    $appliedCoupon = $coupon;
                }
            }

            $finalTotal = max(0, round($orderTotal - $discountAmount, 2));

            $order = Order::create([
                'user_id'         => $userId,
                'payment_method'  => $paymentMethod,
                'status'          => 'pending',
                'total'           => $finalTotal,
                'address'         => $address,
                'latitude'        => $latitude,
                'longitude'       => $longitude,
                'coupon_code'     => $appliedCoupon ? $appliedCoupon->code : null,
                'discount_amount' => $discountAmount,
            ]);

            foreach ($preparedItems as $itemData) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $itemData['product_id'],
                    'quantity'   => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'line_total' => $itemData['line_total'],
                    'size'       => $itemData['size'],
                    'color'      => $itemData['color'],
                ]);

                $this->deductStock($itemData['product'], $itemData['quantity'], $itemData['size']);
            }

            $order->load('items.product');

            return $order;
        });

        $user = User::find($userId);
        if ($user) {
            $order->setRelation('user', $user);
        }

        // Send order emails synchronously, in this same process. A detached
        // shell subprocess was used previously to avoid blocking on SMTP, but
        // that doesn't reliably survive a Docker container's process
        // lifecycle in production — the orphaned background process can be
        // killed before it finishes, so the email silently never sends.
        // QUEUE_CONNECTION=sync means a real queue wouldn't give true async
        // delivery here either, so sending inline is both simpler and
        // actually reliable. SendOrderEmails already catches per-email
        // failures internally and only logs them, so this never blocks
        // checkout on an SMTP problem.
        try {
            Artisan::call('order:send-emails', ['orderId' => (int) $order->id]);
        } catch (\Throwable $e) {
            Log::warning('Order email dispatch failed: ' . $e->getMessage());
        }

        return $order;
    }

    private function validateStock(Product $product, int $qty, ?string $size): void
    {
        $sizesStock = $product->sizes_stock ?? [];
        if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
            $available = (int) $sizesStock[$size];
            if ($available < $qty) {
                throw new \App\Exceptions\InsufficientStockException(
                    "Not enough stock for size {$size} of {$product->name}"
                );
            }
            return;
        }

        if (!is_null($product->stock) && $product->stock < $qty) {
            throw new \App\Exceptions\InsufficientStockException(
                "Not enough stock for {$product->name}"
            );
        }
    }

    /**
     * Take an order's units back OUT of stock. Mirror of restoreStock(),
     * used when a rejected order (whose stock was restored) is re-activated.
     */
    public function reapplyStock(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $size = $item->size;
            $qty  = $item->quantity;

            $sizesStock = $product->sizes_stock ?? [];
            if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
                $sizesStock[$size]    = max(0, (int) $sizesStock[$size] - $qty);
                $product->sizes_stock = $sizesStock;
            }

            if (! is_null($product->stock)) {
                $product->stock = max(0, (int) $product->stock - $qty);
            }

            $product->save();
        }
    }

    public function restoreStock(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $size = $item->size;
            $qty  = $item->quantity;

            $sizesStock = $product->sizes_stock ?? [];
            if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
                $sizesStock[$size]    = (int) $sizesStock[$size] + $qty;
                $product->sizes_stock = $sizesStock;
            }

            if (! is_null($product->stock)) {
                $product->stock = (int) $product->stock + $qty;
            }

            $product->save();
        }
    }

    private function deductStock(Product $product, int $qty, ?string $size): void
    {
        $sizesStock = $product->sizes_stock ?? [];
        if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
            $sizesStock[$size]    = max(0, (int) $sizesStock[$size] - $qty);
            $product->sizes_stock = $sizesStock;
        }

        if (!is_null($product->stock)) {
            $product->stock = max(0, (int) $product->stock - $qty);
        }

        $product->save();
    }
}
