<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // GET /admin/orders
    public function index()
    {
        $orders = Order::with('user')
            ->latest()
            ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    // GET /admin/orders/{order}
    public function show(Order $order)
    {
        // load items + product for each item + user
        $order->load(['items.product', 'user']);

        return view('admin.orders.show', compact('order'));
    }

    // PATCH /admin/orders/{order}
    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,paid,shipped,cancelled'],
        ]);

        $order->status = $data['status'];
        $order->save();

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated.');
    }
}
