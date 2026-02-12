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
        .order-info { background: #f9f9f9; padding: 16px; border-radius: 6px; margin: 16px 0; }
        .order-info p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th { background: #f0f0f0; text-align: left; padding: 10px; font-size: 13px; }
        td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; }
        .footer { background: #f4f4f4; padding: 16px; text-align: center; color: #888; font-size: 12px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; color: #fff; background: #e67e22; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>XTREMEFIT</h1>
        </div>
        <div class="body">
            <h2>Thank you for your order!</h2>
            <p>Hi {{ $order->user->name ?? 'Customer' }},</p>
            <p>Your order has been placed successfully. Here are the details:</p>

            <div class="order-info">
                <p><strong>Order #:</strong> {{ $order->id }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('M d, Y h:i A') }}</p>
                <p><strong>Payment:</strong> {{ ucfirst($order->payment_method) }}</p>
                <p><strong>Status:</strong> <span class="badge">{{ ucfirst($order->status) }}</span></p>
                @if($order->address)
                    <p><strong>Delivery Address:</strong> {{ $order->address }}</p>
                @endif
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->size ?? '—' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td>${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="4">Total</td>
                        <td>${{ number_format($order->total, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <p>We'll notify you when your order status changes.</p>
            <p>— The XTREMEFIT Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} XTREMEFIT. All rights reserved.
        </div>
    </div>
</body>
</html>
