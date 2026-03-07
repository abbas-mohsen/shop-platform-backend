<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: #1a1a2e; color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .body { padding: 24px; color: #333; }
        .body h2 { color: #1a1a2e; margin-top: 0; }
        .status-box { background: #f9f9f9; padding: 20px; border-radius: 6px; margin: 16px 0; text-align: center; }
        .status-from { color: #888; font-size: 14px; text-decoration: line-through; }
        .status-arrow { font-size: 24px; margin: 0 12px; color: #888; }
        .status-to { font-size: 18px; font-weight: bold; padding: 6px 16px; border-radius: 16px; color: #fff; }
        .status-pending   { background: #e67e22; }
        .status-approved  { background: #27ae60; }
        .status-delivered { background: #2980b9; }
        .status-rejected  { background: #e74c3c; }
        .status-cancelled { background: #e74c3c; }
        .order-info { margin: 16px 0; }
        .order-info p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th { background: #f0f0f0; text-align: left; padding: 8px 10px; font-size: 12px; color: #555; }
        td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 13px; }
        .footer { background: #f4f4f4; padding: 16px; text-align: center; color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>XTREMEFIT</h1>
        </div>
        <div class="body">
            <h2>Order Status Update</h2>
            <p>Hi {{ $order->user->name ?? 'Customer' }},</p>
            <p>Your order status has been updated:</p>

            <div class="status-box">
                <span class="status-from">{{ ucfirst($oldStatus) }}</span>
                <span class="status-arrow">→</span>
                <span class="status-to status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
            </div>

            <div class="order-info">
                <p><strong>Order #:</strong> {{ $order->id }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('M d, Y h:i A') }}</p>
                <p><strong>Total:</strong> ${{ number_format($order->total, 2) }}</p>
            </div>

            @if($order->items && $order->items->count())
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Size / Color</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>
                            {{ $item->size ?? '—' }}
                            @if($item->color)
                                <br>
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $item->color }};border:1px solid #ccc;vertical-align:middle;margin-right:3px;"></span><span style="font-size:11px;color:#666;">{{ $item->color }}</span>
                            @endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            @if($order->status === 'shipped')
                <p>Your order is on its way! You should receive it soon.</p>
            @elseif($order->status === 'paid')
                <p>Your payment has been confirmed. We're preparing your order.</p>
            @elseif($order->status === 'cancelled')
                <p>Your order has been cancelled. If you have any questions, please contact us.</p>
            @endif

            <p>— The XTREMEFIT Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} XTREMEFIT. All rights reserved.
        </div>
    </div>
</body>
</html>
