@extends('layouts.app')

@section('title', 'Order #'.$order->id)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- SUMMARY CARD --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-body">

                    <div class="d-flex flex-column flex-md-row justify-content-between mb-2">
                        <div>
                            <h5 class="card-title mb-1">Order #{{ $order->id }}</h5>
                            <p class="mb-1 text-muted">
                                Placed on {{ $order->created_at->format('Y-m-d H:i') }}
                            </p>
                            <p class="mb-0">
                                <strong>Total:</strong>
                                ${{ number_format($order->total, 2) }}
                            </p>
                        </div>

                        <div class="text-md-end mt-3 mt-md-0">
                            @php
                                $statusClasses = [
                                    'pending'   => 'warning',
                                    'paid'      => 'success',
                                    'shipped'   => 'primary',
                                    'cancelled' => 'danger',
                                ];
                                $statusClass = isset($statusClasses[$order->status])
                                    ? $statusClasses[$order->status]
                                    : 'secondary';
                            @endphp

                            <span class="badge bg-{{ $statusClass }} mb-2">
                                {{ ucfirst($order->status) }}
                            </span>

                            <p class="mb-1">
                                <strong>Payment:</strong>
                                {{ ucfirst($order->payment_method) }}
                            </p>
                            <p class="mb-0">
                                <strong>Address:</strong>
                                {{ $order->address }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ITEMS TABLE --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Items</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Unit price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->product->name ?? 'Product #'.$item->product_id }}
                                    </td>
                                    <td class="text-end">
                                        ${{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="text-center">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="text-end">
                                        ${{ number_format($item->line_total, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th class="text-end">
                                    ${{ number_format($order->total, 2) }}
                                </th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>

                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary mt-3">
                        Back to My Orders
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
