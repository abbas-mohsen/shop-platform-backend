<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardApiController extends Controller
{
    /**
     * GET /api/admin/dashboard/overview
     */
    public function overview(Request $request)
    {
        // Basic totals
        $totalOrders = Order::count();

        // Revenue: only paid/shipped count as revenue
        $totalRevenue = Order::whereIn('status', ['paid', 'shipped'])
            ->sum('total');

        // Orders by status
        $statusCounts = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Last 7 days: revenue + order count per day
        $last7Days = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        // Top products (by quantity sold)
        $topProductsRaw = OrderItem::select(
                'product_id',
                DB::raw('SUM(quantity) as total_qty')
            )
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->with('product')
            ->limit(5)
            ->get();

        $topProducts = $topProductsRaw->map(function ($row) {
            return [
                'product_id'    => $row->product_id,
                'name'          => optional($row->product)->name ?: 'Product #' . $row->product_id,
                'total_qty'     => (int) $row->total_qty,
            ];
        });

        return response()->json([
            'total_orders'   => (int) $totalOrders,
            'total_revenue'  => (float) $totalRevenue,
            'status_counts'  => [
                'pending'   => (int) ($statusCounts['pending'] ?? 0),
                'paid'      => (int) ($statusCounts['paid'] ?? 0),
                'shipped'   => (int) ($statusCounts['shipped'] ?? 0),
                'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
            ],
            'last_7_days'    => $last7Days,
            'top_products'   => $topProducts,
        ]);
    }
}
