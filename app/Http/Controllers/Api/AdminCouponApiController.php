<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCouponApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $coupons = Coupon::orderByDesc('created_at')->get();

        return response()->json($coupons);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'code'             => 'nullable|string|max:50|unique:coupons,code',
            'discount_type'    => 'required|in:percentage,fixed',
            'discount_value'   => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date|after:now',
        ]);

        // Auto-generate code if not provided
        if (empty($data['code'])) {
            do {
                $data['code'] = strtoupper(Str::random(8));
            } while (Coupon::where('code', $data['code'])->exists());
        } else {
            $data['code'] = strtoupper($data['code']);
        }

        $coupon = Coupon::create($data);

        return response()->json($coupon, 201);
    }

    public function toggle(Request $request, Coupon $coupon)
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $coupon->update(['is_active' => ! $coupon->is_active]);

        return response()->json($coupon);
    }

    public function destroy(Request $request, Coupon $coupon)
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }
}
