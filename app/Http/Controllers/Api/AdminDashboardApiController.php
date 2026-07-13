<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardApiController extends Controller
{
    /**
     * Dashboard overview — cached for 5 minutes to reduce expensive aggregate queries.
     */
    public function overview()
    {
        $data = Cache::remember('admin:dashboard:overview', 300, function () {
            return $this->buildOverviewData();
        });

        return response()->json($data);
    }

    /** A size (or a product's total) at or below this counts as low stock. */
    public const LOW_STOCK_THRESHOLD = 5;

    /** Statuses that count as realized sales. */
    private const REVENUE_STATUSES = ['approved', 'delivered'];

    private function buildOverviewData(): array
    {
        $totalOrders  = Order::count();
        $totalRevenue = Order::whereIn('status', self::REVENUE_STATUSES)->sum('total');

        $statusCounts = Order::select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // Daily orders + realized revenue for the last 30 days (charting)
        $last30Days = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw("SUM(CASE WHEN status IN ('approved','delivered') THEN total ELSE 0 END) as revenue")
            )
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'order_items.product_id',
                'products.name',
                'products.image',
                DB::raw('SUM(order_items.quantity) as total_qty')
            )
            ->groupBy('order_items.product_id', 'products.name', 'products.image')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $topCustomers = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'orders.user_id',
                'users.name',
                'users.phone',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(orders.total) as total_spent')
            )
            ->whereNotNull('orders.user_id')
            ->whereIn('orders.status', self::REVENUE_STATUSES)
            ->groupBy('orders.user_id', 'users.name', 'users.phone')
            ->orderByDesc('total_spent')
            ->take(5)
            ->get();

        // Low stock is size-aware: a product with healthy total stock can
        // still be about to sell out in a specific size.
        $lowStockProducts = Product::select('id', 'name', 'stock', 'image', 'sizes_stock')
            ->get()
            ->map(function ($p) {
                $lowSizes = collect($p->sizes_stock ?: [])
                    ->filter(fn ($qty) => (int) $qty <= self::LOW_STOCK_THRESHOLD)
                    ->map(fn ($qty, $size) => ['size' => $size, 'stock' => (int) $qty])
                    ->values();

                return [
                    'id'        => $p->id,
                    'name'      => $p->name,
                    'image'     => $p->image,
                    'stock'     => $p->stock,
                    'low_sizes' => $lowSizes,
                ];
            })
            ->filter(fn ($p) => $p['stock'] <= self::LOW_STOCK_THRESHOLD || $p['low_sizes']->isNotEmpty())
            ->sortBy('stock')
            ->take(10)
            ->values();

        $outOfStockCount = Product::where('stock', 0)->count();

        return [
            'total_orders'       => $totalOrders,
            'total_revenue'      => round((float) $totalRevenue, 2),
            'out_of_stock_count' => $outOfStockCount,
            'low_stock_count'    => $lowStockProducts->count(),
            'status_counts' => [
                'pending'   => $statusCounts['pending']   ?? 0,
                'approved'  => $statusCounts['approved']  ?? 0,
                'delivered' => $statusCounts['delivered'] ?? 0,
                'rejected'  => $statusCounts['rejected']  ?? 0,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
            ],
            'last_30_days'       => $last30Days,
            // kept for backward compatibility with the existing dashboard UI
            'last_7_days'        => $last30Days->slice(-7)->values(),
            'top_products'       => $topProducts,
            'top_customers'      => $topCustomers,
            'low_stock_products' => $lowStockProducts,
        ];
    }
}
