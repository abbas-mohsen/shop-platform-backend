<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::user()->orders()->latest()->get();

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load('items.product');

        return view('orders.show', compact('order'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card',
            'address'        => 'required|string|max:255',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        DB::beginTransaction();

        try {
            $total = collect($cart)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });

            $order = Order::create([
                'user_id'        => Auth::id(),
                'total'          => $total,
                'status'         => 'pending',
                'payment_method' => $request->payment_method,
                'address'        => $request->address,
            ]);

            foreach ($cart as $productId => $item) {
                OrderItem::create([
                    'order_id'  => $order->id,
                    'product_id'=> $productId,
                    'quantity'  => $item['quantity'],
                    'price'     => $item['price'],
                ]);
            }

            DB::commit();
            session()->forget('cart');

            return redirect()->route('orders.index')
                ->with('success', 'Order placed successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('cart.index')
                ->with('error', 'Something went wrong. Please try again.');
        }
    }
}
