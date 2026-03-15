<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponApiController extends Controller
{
    /**
     * POST /api/coupons/validate
     * Authenticated users only.
     * Body: { code, cart_total }
     * Returns discount info without consuming the coupon.
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code'       => 'required|string',
            'cart_total' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', strtoupper(trim($request->input('code'))))->first();

        if (! $coupon) {
            return response()->json(['message' => 'Invalid coupon code.'], 422);
        }

        $cartTotal = (float) $request->input('cart_total');

        if (! $coupon->isValidFor($cartTotal)) {
            if (! $coupon->is_active) {
                return response()->json(['message' => 'This coupon is no longer active.'], 422);
            }
            if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                return response()->json(['message' => 'This coupon has expired.'], 422);
            }
            if (! is_null($coupon->max_uses) && $coupon->used_count >= $coupon->max_uses) {
                return response()->json(['message' => 'This coupon has reached its usage limit.'], 422);
            }
            if (! is_null($coupon->min_order_amount) && $cartTotal < $coupon->min_order_amount) {
                return response()->json([
                    'message' => "Minimum order of $" . number_format($coupon->min_order_amount, 2) . " required for this coupon.",
                ], 422);
            }
            return response()->json(['message' => 'This coupon cannot be applied.'], 422);
        }

        $discountAmount = $coupon->calculateDiscount($cartTotal);

        return response()->json([
            'code'            => $coupon->code,
            'discount_type'   => $coupon->discount_type,
            'discount_value'  => $coupon->discount_value,
            'discount_amount' => $discountAmount,
            'final_total'     => max(0, round($cartTotal - $discountAmount, 2)),
        ]);
    }
}
