<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderApiController extends Controller
{
    public function index()
    {
        // load user and items->product for summary
        $orders = Order::with(['user', 'items.product'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,paid,shipped,cancelled'],
        ]);

        $order->status = $data['status'];
        $order->save();

        return response()->json($order);
    }
}