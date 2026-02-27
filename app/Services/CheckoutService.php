<?php

namespace App\Services;

use App\Mail\NewOrderAdmin;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckoutService
{
    /**
     * Process a checkout: validate stock, create order, deduct inventory.
     *
     * @param  int    $userId
     * @param  array  $items           [['product_id'=>int, 'quantity'=>int, 'size'=>?string], ...]
     * @param  string $paymentMethod   'cod' or 'card'
     * @param  string $address
     * @return Order
     *
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function execute(int $userId, array $items, string $paymentMethod, string $address): Order
    {
        $order = DB::transaction(function () use ($userId, $items, $paymentMethod, $address) {
            $orderTotal    = 0;
            $preparedItems = [];

            // 1) Validate stock and calculate totals
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty     = (int) $item['quantity'];
                $size    = $item['size'] ?? null;

                $this->validateStock($product, $qty, $size);

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

            // 2) Create Order
            $order = Order::create([
                'user_id'        => $userId,
                'payment_method' => $paymentMethod,
                'status'         => 'pending',
                'total'          => $orderTotal,
                'address'        => $address,
            ]);

            // 3) Create line items and deduct stock
            foreach ($preparedItems as $itemData) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $itemData['product_id'],
                    'quantity'   => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'line_total' => $itemData['line_total'],
                    'size'       => $itemData['size'],
                ]);

                $this->deductStock($itemData['product'], $itemData['quantity'], $itemData['size']);
            }

            $order->load('items.product');

            return $order;
        });

        // Send emails OUTSIDE the transaction so a mail failure never
        // rolls back a successful order.
        $user = User::find($userId);
        if ($user) {
            $order->setRelation('user', $user);
        }

        // 1) Customer confirmation
        try {
            if ($user && $user->email) {
                Mail::to($user->email)->queue(new OrderConfirmation($order));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Order confirmation email failed: ' . $e->getMessage());
        }

        // 2) Admin / super_admin notification
        try {
            $adminEmails = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                ->whereNotNull('email')
                ->pluck('email')
                ->all();

            foreach ($adminEmails as $email) {
                Mail::to($email)->queue(new NewOrderAdmin($order));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Admin new-order email failed: ' . $e->getMessage());
        }

        return $order;
    }

    /**
     * Validate that enough stock exists for the given product/size/quantity.
     *
     * @throws \App\Exceptions\InsufficientStockException
     */
    private function validateStock(Product $product, int $qty, ?string $size): void
    {
        $sizesStock = $product->sizes_stock ?? [];

        if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
            $available = (int) $sizesStock[$size];
            if ($available < $qty) {
                throw new \App\Exceptions\InsufficientStockException(
                    "Not enough stock for size {$size} of product {$product->name}"
                );
            }
            return;
        }

        // Fall back to global stock
        if (!is_null($product->stock) && $product->stock < $qty) {
            throw new \App\Exceptions\InsufficientStockException(
                "Not enough stock for product {$product->name}"
            );
        }
    }

    /**
     * Restore stock to products when an order is cancelled.
     * Mirrors deductStock in reverse.
     */
    public function restoreStock(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $sizesStock = $product->sizes_stock ?? [];
            $size       = $item->size;

            if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
                $sizesStock[$size]    = (int) $sizesStock[$size] + $item->quantity;
                $product->sizes_stock = $sizesStock;
            }

            if (! is_null($product->stock)) {
                $product->stock = (int) $product->stock + $item->quantity;
            }

            $product->save();
        }
    }

    /**
     * Deduct stock from the product after a successful order.
     */
    private function deductStock(Product $product, int $qty, ?string $size): void
    {
        $sizesStock = $product->sizes_stock ?? [];

        if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
            $sizesStock[$size] = max(0, (int) $sizesStock[$size] - $qty);
            $product->sizes_stock = $sizesStock;
        }

        if (!is_null($product->stock)) {
            $product->stock = max(0, (int) $product->stock - $qty);
        }

        $product->save();
    }
}
