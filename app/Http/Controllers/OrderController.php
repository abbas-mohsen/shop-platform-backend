<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        // Ensure user can only see his own orders
        abort_if($order->user_id !== auth()->id(), 403);

        $order->load('items.product');

        return view('orders.show', compact('order'));
    }

    public function store(Request $request)
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('success', 'Your cart is empty.');
        }

        $total = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        // Simple: assume payment method "cash" for now
        $order = Order::create([
            'user_id'        => auth()->id(),
            'total'   => $total,     // change to 'total' if that's your column
            'status'         => 'pending',
            'payment_method' => 'cash',
        ]);

        foreach ($cart as $item) {
            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item['id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['price'],
                'line_total' => $item['price'] * $item['quantity'],
            ]);
        }

        // Clear cart
        session()->forget('cart');

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order placed successfully.');
    }
}
