@extends('layouts.app')

@section('content')
    <h1>My Orders</h1>

    @forelse($orders as $order)
        <div class="order-card">
            <h3>Order #{{ $order->id }}</h3>
            <p>Date: {{ $order->created_at->format('Y-m-d H:i') }}</p>
            <p>Total: ${{ number_format($order->total, 2) }}</p>
            <p>Status: {{ ucfirst($order->status) }}</p>
            <a href="{{ route('orders.show', $order) }}">View details</a>
        </div>
    @empty
        <p>No orders yet.</p>
    @endforelse
@endsection
