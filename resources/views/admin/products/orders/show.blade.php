@extends('layouts.app')

@section('title', 'Order #'.$order->id)

@section('content')
<div class="container">
    <a href="{{ route('admin.orders.index') }}" class="btn btn-link mb-3">&larr; Back to orders</a>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Order Summary</h5>

                    <p class="mb-1">
                        <strong>Order #:</strong> {{ $order->id }}
                    </p>
                    <p class="mb-1">
                        <strong>Placed on:</strong> {{ $order->created_at->format('Y-m-d H:i') }}
                    </p>
                    <p class="mb-1">
                        <strong>Customer:</strong> {{ optional($order->user)->name ?? 'Guest' }}
                    </p>
                    <p class="mb-1">
                        <strong>Email:</strong> {{ optional($order->user)->email ?? '—' }}
                    </p>
                    <p class="mb-1">
                        <strong>Address:</strong> {{ $order->address ?? '—' }}
                    </p>
                    <p class="mb-1">
                        <strong>Payment:</strong> {{ ucfirst($order->payment_method ?? 'n/a') }}
                    </p>

                    @php
                        $statusColors = [
                            'pending'   => 'warning',
                            'paid'      => 'success',
                            'shipped'   => 'primary',
                            'cancelled' => 'danger',
                        ];
                        $statusClass = $statusColors[$order->status] ?? 'secondary';
                    @endphp

                    <p class="mb-0 mt-2">
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $statusClass }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- Status update form --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Update Status</h5>
                    <form action="{{ route('admin.orders.update', $order) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <select name="status"
                                    class="form-select @error('status') is-invalid @enderror">
                                <option value="pending"   {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid"      {{ $order->status === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="shipped"   {{ $order->status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <button class="btn btn-primary" type="submit">Save</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="col-md-6 mt-4 mt-md-0">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Items</h5>

                    <table class="table align-middle mb-0">
                        <thead class="table-light">
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
                                <td>{{ optional($item->product)->name ?? 'Deleted product' }}</td>
                                <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">${{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th class="text-end">${{ number_format($order->total, 2) }}</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
