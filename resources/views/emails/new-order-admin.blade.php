<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: #1a1a2e; color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0 0 4px; font-size: 22px; letter-spacing: 1px; }
        .header p { margin: 0; font-size: 13px; color: #aaa; }
        .alert-bar { background: #e67e22; color: #fff; text-align: center; padding: 10px 24px; font-size: 14px; font-weight: bold; letter-spacing: 0.5px; }
        .body { padding: 28px 24px; color: #333; }
        .body h2 { color: #1a1a2e; margin-top: 0; font-size: 18px; }
        .section-label { font-size: 11px; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 1px; margin: 20px 0 6px; }
        .info-box { background: #f9f9f9; padding: 14px 16px; border-radius: 6px; }
        .info-box p { margin: 4px 0; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 0; }
        th { background: #f0f0f0; text-align: left; padding: 10px; font-size: 12px; color: #555; }
        td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; }
        .total-row td { font-weight: bold; border-top: 2px solid #ddd; border-bottom: none; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; color: #fff; background: #e67e22; }
        .footer { background: #f4f4f4; padding: 16px; text-align: center; color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>XTREMEFIT</h1>
            <p>Admin Notification</p>
        </div>
        <div class="alert-bar">
            🛒 New Order Received — Action May Be Required
        </div>
        <div class="body">
            <h2>Order #{{ $order->id }} has been placed</h2>
            <p>A customer just completed a new order. Here's a summary:</p>

            <p class="section-label">Customer</p>
            <div class="info-box">
                <p><strong>Name:</strong> {{ $order->user->name ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $order->user->email ?? 'N/A' }}</p>
                @if($order->address)
                    <p><strong>Delivery Address:</strong> {{ $order->address }}</p>
                @endif
            </div>

            <p class="section-label">Order Details</p>
            <div class="info-box">
                <p><strong>Order #:</strong> {{ $order->id }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('M d, Y h:i A') }}</p>
                <p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}</p>
                <p><strong>Status:</strong> <span class="badge">{{ ucfirst($order->status) }}</span></p>
            </div>

            <p class="section-label">Items</p>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
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
                        <td colspan="4">Order Total</td>
                        <td>${{ number_format($order->total, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 24px; color: #888; font-size: 12px;">
                Log in to the admin panel to manage this order.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} XTREMEFIT. This email was sent to admins only.
        </div>
    </div>
</body>
</html>
