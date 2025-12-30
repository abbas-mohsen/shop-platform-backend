@extends('layouts.app')

@section('title', 'My Orders')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">My Orders</h2>

        <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm">
            Continue Shopping
        </a>
    </div>

    @forelse($orders as $order)
        @php
            $statusClasses = [
                'pending'    => 'warning',
                'processing' => 'info',
                'completed'  => 'success',
                'delivered'  => 'success',
                'cancelled'  => 'danger',
            ];
            $badgeClass = $statusClasses[strtolower($order->status)] ?? 'secondary';
        @endphp

        <div class="card mb-3 shadow-sm">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="mb-2 mb-md-0">
                    <h5 class="card-title mb-1">
                        Order #{{ $order->id }}
                    </h5>
                    <div class="text-muted small">
                        Placed on {{ $order->created_at->format('Y-m-d H:i') }}
                    </div>
                    <div class="mt-2">
                        <span class="fw-semibold">Total:</span>
                        <span class="fw-bold">${{ number_format($order->total, 2) }}</span>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                    <span class="badge bg-{{ $badgeClass }} text-uppercase">
                        {{ ucfirst($order->status) }}
                    </span>

                    <a href="{{ route('orders.show', $order) }}"
                       class="btn btn-sm btn-outline-primary">
                        View details
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5">
            <h5 class="mb-3">You don’t have any orders yet.</h5>
            <a href="{{ route('products.index') }}" class="btn btn-primary">
                Browse Products
            </a>
        </div>
    @endforelse

    @if(method_exists($orders, 'links'))
        <div class="mt-3">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
