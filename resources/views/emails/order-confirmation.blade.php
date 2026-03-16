<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: #1a1a2e; color: #fff; padding: 20px 24px; text-align: center; }
        .header img { height: 56px; max-width: 180px; object-fit: contain; display: block; margin: 0 auto; }
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
            <img src="{{ $message->embed(public_path('images/logo.jpg')) }}" alt="XTREMEFIT">
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
                        <th>Size / Color</th>
                        <th>Qty</th>
                        <th>Price</th>
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
                                <span style="display:inline-block;width:11px;height:11px;background:{{ $item->color }};border:1px solid #ccc;vertical-align:middle;margin-right:4px;"></span><span style="font-size:11px;color:#555;">{{ $item->color }}</span>
                            @endif
                        </td>
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

            <div style="margin-top:16px;padding:12px 16px;border-radius:6px;background:#f0f7ff;border-left:3px solid #1a73e8;font-size:13px;color:#333;">
                📎 <strong>Invoice attached:</strong> Your invoice <strong>INV-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</strong> is attached to this email as a PDF for your records.
            </div>

            <div style="margin-top:16px;padding:12px 16px;border-radius:6px;background:#fff5f5;border-left:3px solid #e02020;font-size:12px;color:#666;line-height:1.6;">
                <strong style="color:#e02020;">Cancellation Policy:</strong> Orders can only be cancelled while in <em>Pending</em> status and within <strong>24 hours</strong> of placement. After this window, cancellation is no longer available — please contact us for assistance.
            </div>

            <p style="margin-top:16px;">— The XTREMEFIT Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} XTREMEFIT. All rights reserved.
        </div>
    </div>
</body>
</html>
