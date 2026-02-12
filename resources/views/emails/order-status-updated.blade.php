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
        .status-paid      { background: #27ae60; }
        .status-shipped   { background: #2980b9; }
        .status-cancelled { background: #e74c3c; }
        .order-info { margin: 16px 0; }
        .order-info p { margin: 4px 0; }
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
