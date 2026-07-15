XTREMEFIT — New Order #{{ $order->id }}

A new order has been placed.

Customer: {{ $order->user->name ?? 'N/A' }} ({{ $order->user->email ?? 'N/A' }})
@if($order->address)Delivery address: {{ $order->address }}
@endif
Order #: {{ $order->id }}
Date: {{ $order->created_at->format('M d, Y h:i A') }}
Payment: {{ ucfirst($order->payment_method) }}
Status: {{ ucfirst($order->status) }}

Items:
@foreach($order->items as $item)
- {{ $item->product->name ?? 'N/A' }} — {{ $item->size ?? 'One size' }}@if($item->color), {{ $item->color_name }}@endif x{{ $item->quantity }} — ${{ number_format($item->line_total, 2) }}
@endforeach

Order total: ${{ number_format($order->total, 2) }}

Log in to the admin panel to manage this order.
