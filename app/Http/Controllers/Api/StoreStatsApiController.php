<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StoreStatsApiController extends Controller
{
    /**
     * GET /api/stats (public)
     *
     * Aggregate, non-identifying counts for the homepage stats strip.
     *
     * These are the real figures from the database. The homepage has no
     * hardcoded fallbacks on purpose: a count of zero means the stat is
     * simply not displayed yet, rather than being replaced by an invented
     * number. As real orders come in, the strip fills itself out.
     *
     * Cached briefly so an anonymous homepage hit never costs three
     * aggregate queries against the production database.
     */
    public function index(): JsonResponse
    {
        $stats = Cache::remember('store_public_stats', 300, function () {
            // Only delivered orders count as "shipped" — pending, rejected and
            // cancelled orders have not reached anyone.
            $delivered = Order::where('status', 'delivered');

            return [
                'orders'    => (clone $delivered)->count(),
                'customers' => (clone $delivered)->distinct()->count('user_id'),
                'products'  => Product::count(),
            ];
        });

        return response()->json($stats);
    }
}
