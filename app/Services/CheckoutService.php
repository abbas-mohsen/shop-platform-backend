<?php

namespace App\Services;

use App\Mail\NewOrderAdmin;
use App\Mail\OrderConfirmation;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckoutService
{
    public function execute(int $userId, array $items, string $paymentMethod, string $address, ?string $couponCode = null): Order
    {
        $order = DB::transaction(function () use ($userId, $items, $paymentMethod, $address, $couponCode) {
            $orderTotal    = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty     = (int) $item['quantity'];
                $size    = $item['size'] ?? null;
                $color   = $item['color'] ?? null;

                $this->validateStock($product, $qty, $size, $color);

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

                $this->deductStock($itemData['product'], $itemData['quantity'], $itemData['size'], $itemData['color'] ?? null);
            }

            $order->load('items.product');

            return $order;
        });

        $user = User::find($userId);
        if ($user) {
            $order->setRelation('user', $user);
        }

        try {
            if ($user && $user->email) {
                Mail::to($user->email)->queue(new OrderConfirmation($order));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Order confirmation email failed: ' . $e->getMessage());
        }

        try {
            $adminEmails = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                ->whereNotNull('email')
                ->pluck('email')
                ->all();

            foreach ($adminEmails as $email) {
                Mail::to($email)->queue(new NewOrderAdmin($order));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Admin new-order email failed: ' . $e->getMessage());
        }

        return $order;
    }

    private function validateStock(Product $product, int $qty, ?string $size, ?string $color = null): void
    {
        $colorsStock = $product->colors_stock ?? [];
        if ($size && $color && is_array($colorsStock) && isset($colorsStock[$size]) && array_key_exists($color, $colorsStock[$size])) {
            $available = (int) $colorsStock[$size][$color];
            if ($available < $qty) {
                throw new \App\Exceptions\InsufficientStockException(
                    "Not enough stock for size {$size} of {$product->name}"
                );
            }
            return;
        }

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

    public function restoreStock(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $size  = $item->size;
            $color = $item->color;
            $qty   = $item->quantity;

            $colorsStock = $product->colors_stock ?? [];
            if ($size && $color && is_array($colorsStock) && isset($colorsStock[$size]) && array_key_exists($color, $colorsStock[$size])) {
                $colorsStock[$size][$color] = (int) $colorsStock[$size][$color] + $qty;
                $product->colors_stock      = $colorsStock;
            }

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

    private function deductStock(Product $product, int $qty, ?string $size, ?string $color = null): void
    {
        $colorsStock = $product->colors_stock ?? [];
        if ($size && $color && is_array($colorsStock) && isset($colorsStock[$size]) && array_key_exists($color, $colorsStock[$size])) {
            $colorsStock[$size][$color] = max(0, (int) $colorsStock[$size][$color] - $qty);
            $product->colors_stock      = $colorsStock;
        }

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
