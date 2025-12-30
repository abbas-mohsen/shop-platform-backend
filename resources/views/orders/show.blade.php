@extends('layouts.app')

@section('content')
    <h1>Order #{{ $order->id }}</h1>
    <p>Status: {{ ucfirst($order->status) }}</p>
    <p>Total: ${{ number_format($order->total, 2) }}</p>
    <p>Payment: {{ ucfirst($order->payment_method) }}</p>
    <p>Address: {{ $order->address }}</p>

    <h2>Items</h2>
    <table>
        <thead>
        <tr>
            <th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>${{ number_format($item->price, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
