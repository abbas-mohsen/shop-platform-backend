<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; padding: 36px 42px; }

    .header { width: 100%; border-bottom: 3px solid #e02020; padding-bottom: 14px; margin-bottom: 24px; }
    .brand { font-size: 22px; font-weight: bold; letter-spacing: 2px; color: #111; }
    .brand span { color: #e02020; }
    .header-right { float: right; text-align: right; font-size: 11px; color: #666; }
    .invoice-title { font-size: 15px; font-weight: bold; color: #111; margin-bottom: 2px; }

    .meta { width: 100%; margin-bottom: 26px; }
    .meta td { vertical-align: top; padding: 0; }
    .meta h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 5px; }
    .meta p { font-size: 12px; line-height: 1.55; }

    .status-badge {
        display: inline-block; padding: 3px 12px; border-radius: 10px;
        font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;
        color: #fff;
    }
    .status-pending   { background: #b45309; }
    .status-approved  { background: #15803d; }
    .status-delivered { background: #1d4ed8; }
    .status-rejected  { background: #b91c1c; }
    .status-cancelled { background: #6b7280; }

    table.items { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
    table.items th {
        background: #111; color: #fff; text-align: left;
        font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
        padding: 8px 10px;
    }
    table.items th.num, table.items td.num { text-align: right; }
    table.items td { padding: 8px 10px; border-bottom: 1px solid #e5e5e5; font-size: 12px; }
    .muted { color: #888; font-size: 10px; }

    table.totals { width: 42%; margin-left: 58%; border-collapse: collapse; }
    table.totals td { padding: 5px 10px; font-size: 12px; }
    table.totals td.num { text-align: right; }
    table.totals tr.grand td {
        border-top: 2px solid #111; font-size: 14px; font-weight: bold; padding-top: 8px;
    }
    .discount { color: #15803d; }

    .footer {
        position: fixed; bottom: 24px; left: 42px; right: 42px;
        border-top: 1px solid #ddd; padding-top: 10px;
        font-size: 10px; color: #999; text-align: center; line-height: 1.6;
    }
</style>
</head>
<body>

    <div class="header">
        <div class="header-right">
            <div class="invoice-title">INVOICE #{{ $order->id }}</div>
            <div>Issued: {{ $order->created_at->format('F j, Y — g:i A') }}</div>
            <div style="margin-top: 6px;">
                <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
            </div>
        </div>
        <div class="brand">XTREME<span>FIT</span></div>
        <div class="muted" style="margin-top: 3px;">Sportswear built for training and everyday movement</div>
    </div>

    <table class="meta">
        <tr>
            <td width="50%">
                <h4>Billed To</h4>
                <p>
                    <strong>{{ $order->user->name ?? 'Guest' }}</strong><br>
                    {{ $order->user->email ?? '' }}<br>
                    @if($order->user && $order->user->phone) {{ $order->user->phone }}<br> @endif
                </p>
            </td>
            <td width="50%">
                <h4>Delivery Address</h4>
                <p>{{ $order->address ?: '—' }}</p>
                <h4 style="margin-top: 10px;">Payment Method</h4>
                <p>{{ $order->payment_method === 'card' ? 'Card' : 'Cash on Delivery' }}</p>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th>Size</th>
                <th>Color</th>
                <th class="num">Qty</th>
                <th class="num">Unit Price</th>
                <th class="num">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'Product #' . $item->product_id }}</td>
                    <td>{{ $item->size ?: '—' }}</td>
                    <td>
                        @if($item->color)
                            <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #bbb; background: {{ $item->color }};"></span>
                            {{ $item->color }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="num">${{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="num">${{ number_format($subtotal, 2) }}</td>
        </tr>
        @if($order->discount_amount > 0)
            <tr class="discount">
                <td>Discount @if($order->coupon_code) ({{ $order->coupon_code }}) @endif</td>
                <td class="num">-${{ number_format($order->discount_amount, 2) }}</td>
            </tr>
        @endif
        @if($delivery > 0)
            <tr>
                <td>Delivery</td>
                <td class="num">${{ number_format($delivery, 2) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>Total</td>
            <td class="num">${{ number_format($order->total, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        XTREMEFIT · {{ $contactAddress ?: 'Beirut, Lebanon' }}
        @if($contactPhone) · {{ $contactPhone }} @endif
        @if($contactEmail) · {{ $contactEmail }} @endif
        <br>
        Thank you for training with us. Cash on delivery — pay when your order is in your hands.
    </div>

</body>
</html>
