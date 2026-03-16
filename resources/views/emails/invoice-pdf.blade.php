<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

        /* ── HEADER ── */
        .hd { background: #0f1035; padding: 26px 40px; }
        .hd-inner { display: table; width: 100%; }
        .hd-logo  { display: table-cell; vertical-align: middle; width: 50%; }
        .hd-title { display: table-cell; vertical-align: middle; width: 50%; text-align: right; }
        .hd-logo img { height: 52px; max-width: 170px; }
        .inv-word { font-size: 38px; font-weight: bold; color: #ffffff; letter-spacing: 5px; text-transform: uppercase; }
        .inv-sub  { font-size: 9px; color: #8888bb; letter-spacing: 2px; text-transform: uppercase; margin-top: 3px; }

        /* ── RED STRIPE ── */
        .stripe-red { background: #e02020; height: 4px; }
        .stripe-dark { background: #0f1035; height: 2px; }

        /* ── META BAR ── */
        .meta { background: #f5f5f8; padding: 13px 40px; border-bottom: 1px solid #e2e2ee; }
        .meta-inner { display: table; width: 100%; }
        .meta-cell  { display: table-cell; vertical-align: middle; width: 33.33%; }
        .meta-cell.right { text-align: right; }
        .meta-cell.center { text-align: center; }
        .mk { font-size: 8px; text-transform: uppercase; letter-spacing: 1.2px; color: #999; font-weight: bold; margin-bottom: 3px; }
        .mv { font-size: 14px; font-weight: bold; color: #0f1035; }

        /* ── BODY ── */
        .body { padding: 26px 40px 30px; }

        /* ── BILL ROW ── */
        .bill-row { display: table; width: 100%; margin-bottom: 26px; }
        .bill-col  { display: table-cell; vertical-align: top; width: 50%; }
        .bill-col.right { text-align: right; padding-left: 20px; }
        .bill-col.left  { padding-right: 20px; }
        .sec-label {
            font-size: 8px; text-transform: uppercase; letter-spacing: 1.5px;
            color: #e02020; font-weight: bold;
            border-bottom: 1px solid #e8e8e8; padding-bottom: 5px; margin-bottom: 9px;
        }
        .bill-name   { font-size: 14px; font-weight: bold; color: #0f1035; margin-bottom: 5px; }
        .bill-detail { font-size: 11px; color: #555; line-height: 1.8; }

        /* ── STATUS BADGE ── */
        .badge {
            display: inline-block; padding: 2px 9px;
            font-size: 9px; font-weight: bold; color: #fff;
            background: #e67e22; text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* ── ITEMS TABLE ── */
        .t { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .t thead th {
            background: #0f1035; color: #fff;
            padding: 10px 12px; font-size: 9px;
            text-transform: uppercase; letter-spacing: 1px; font-weight: bold;
        }
        .t thead th.r { text-align: right; }
        .t thead th.c { text-align: center; }
        .t tbody tr.odd  td { background: #ffffff; }
        .t tbody tr.even td { background: #f6f6fb; }
        .t tbody td {
            padding: 10px 12px; font-size: 11px;
            color: #333; vertical-align: middle;
            border-bottom: 1px solid #ededf3;
        }
        .t tbody td.sub  { text-align: right; font-weight: 600; color: #0f1035; }
        .t tbody td.qty  { text-align: center; font-weight: 600; }
        .t tbody td.up   { text-align: right; }
        .t tbody td.name { font-weight: 600; color: #0f1035; }
        .color-dot {
            display: inline-block; width: 9px; height: 9px;
            border: 1px solid #aaa; vertical-align: middle; margin-right: 3px;
        }

        /* ── TOTALS LAYOUT ── */
        .bot { display: table; width: 100%; margin-top: 8px; }
        .bot-notes { display: table-cell; vertical-align: top; width: 54%; padding-right: 20px; }
        .bot-totals { display: table-cell; vertical-align: top; width: 46%; }

        /* Notes box */
        .notes { background: #f5f5f8; border-left: 3px solid #e02020; padding: 12px 14px; }
        .notes-ttl { font-size: 8px; text-transform: uppercase; letter-spacing: 1.2px; color: #e02020; font-weight: bold; margin-bottom: 6px; }
        .notes-txt { font-size: 10px; color: #666; line-height: 1.7; }

        /* Totals rows */
        .tl { display: table; width: 100%; border-bottom: 1px solid #ececf2; }
        .tl-lbl { display: table-cell; padding: 7px 10px; font-size: 11px; color: #666; }
        .tl-amt { display: table-cell; padding: 7px 10px; font-size: 11px; text-align: right; color: #333; }
        .tl-amt.disc { color: #e02020; }
        .grand { display: table; width: 100%; background: #0f1035; margin-top: 7px; }
        .grand-lbl { display: table-cell; padding: 11px 12px; font-size: 11px; font-weight: bold; color: #fff; text-transform: uppercase; letter-spacing: 1px; }
        .grand-amt { display: table-cell; padding: 11px 12px; font-size: 17px; font-weight: bold; color: #e02020; text-align: right; }

        /* ── FOOTER ── */
        .footer-stripe { background: #e02020; height: 3px; margin-top: 30px; }
        .footer { background: #0f1035; padding: 14px 40px; text-align: center; }
        .footer-brand { font-size: 13px; font-weight: bold; color: #fff; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 4px; }
        .footer-text  { font-size: 9px; color: #8888bb; letter-spacing: 0.5px; }
    </style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="hd">
    <div class="hd-inner">
        <div class="hd-logo">
            @php
                $logoPath = public_path('images/logo.jpg');
                $logoB64  = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
            @endphp
            @if($logoB64)
                <img src="data:image/jpeg;base64,{{ $logoB64 }}" alt="XTREMEFIT">
            @endif
        </div>
        <div class="hd-title">
            <div class="inv-word">Invoice</div>
            <div class="inv-sub">Tax Invoice &amp; Receipt</div>
        </div>
    </div>
</div>

<div class="stripe-red"></div>

{{-- ── META BAR ── --}}
<div class="meta">
    <div class="meta-inner">
        <div class="meta-cell">
            <div class="mk">Invoice No.</div>
            <div class="mv">INV-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</div>
        </div>
        <div class="meta-cell center">
            <div class="mk">Issue Date</div>
            <div class="mv">{{ $order->created_at->format('d M Y') }}</div>
        </div>
        <div class="meta-cell right">
            <div class="mk">Order Reference</div>
            <div class="mv">#{{ $order->id }}</div>
        </div>
    </div>
</div>

<div class="body">

    {{-- ── BILLING INFO ── --}}
    <div class="bill-row">
        <div class="bill-col left">
            <div class="sec-label">Bill To</div>
            <div class="bill-name">{{ $order->user->name ?? 'Customer' }}</div>
            <div class="bill-detail">
                {{ $order->user->email ?? '' }}
                @if($order->address)
                    <br>{{ $order->address }}
                @endif
            </div>
        </div>
        <div class="bill-col right">
            <div class="sec-label">Payment Info</div>
            <div class="bill-detail">
                <strong>Method:</strong> {{ ucfirst($order->payment_method) }}<br>
                <strong>Status:</strong>&nbsp;<span class="badge">{{ ucfirst($order->status) }}</span><br>
                <strong>Time:</strong> {{ $order->created_at->format('h:i A') }}
            </div>
        </div>
    </div>

    {{-- ── ITEMS TABLE ── --}}
    <table class="t">
        <thead>
            <tr>
                <th style="width:38%; text-align:left;">Product</th>
                <th style="width:20%; text-align:left;">Size / Color</th>
                <th class="c" style="width:9%;">Qty</th>
                <th class="r" style="width:15%;">Unit Price</th>
                <th class="r" style="width:18%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr class="{{ $loop->even ? 'even' : 'odd' }}">
                <td class="name">{{ $item->product->name ?? 'N/A' }}</td>
                <td>
                    @if($item->size)<span style="font-weight:600;">{{ $item->size }}</span>@else<span style="color:#bbb;">—</span>@endif
                    @if($item->color)
                        &nbsp;<span class="color-dot" style="background:{{ $item->color }};"></span><span style="font-size:10px;color:#666;">{{ $item->color }}</span>
                    @endif
                </td>
                <td class="qty">{{ $item->quantity }}</td>
                <td class="up">${{ number_format($item->unit_price, 2) }}</td>
                <td class="sub">${{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── BOTTOM: NOTES + TOTALS ── --}}
    <div class="bot">
        <div class="bot-notes">
            <div class="notes">
                <div class="notes-ttl">Important Notes</div>
                <div class="notes-txt">
                    Thank you for choosing XTREMEFIT. Orders may be cancelled within 24 hours of placement while status is <em>Pending</em>. For assistance, please reach out to our support team. This invoice serves as your official receipt.
                </div>
            </div>
        </div>
        <div class="bot-totals">
            @if(isset($order->discount_amount) && $order->discount_amount > 0)
            <div class="tl">
                <div class="tl-lbl">Subtotal</div>
                <div class="tl-amt">${{ number_format($order->total + $order->discount_amount, 2) }}</div>
            </div>
            <div class="tl">
                <div class="tl-lbl">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</div>
                <div class="tl-amt disc">−${{ number_format($order->discount_amount, 2) }}</div>
            </div>
            @endif
            <div class="grand">
                <div class="grand-lbl">Amount Due</div>
                <div class="grand-amt">${{ number_format($order->total, 2) }}</div>
            </div>
        </div>
    </div>

</div>

{{-- ── FOOTER ── --}}
<div class="footer-stripe"></div>
<div class="footer">
    <div class="footer-brand">XTREMEFIT</div>
    <div class="footer-text">
        This is a computer-generated invoice and does not require a signature.
        &copy; {{ date('Y') }} XTREMEFIT. All rights reserved.
    </div>
</div>

</body>
</html>
