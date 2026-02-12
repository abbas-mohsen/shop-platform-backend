<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
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

    private function buildOverviewData(): array
    {
        $totalOrders  = Order::count();
        $totalRevenue = Order::whereIn('status', ['paid', 'shipped'])->sum('total');

        $statusCounts = Order::select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $last7Days = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
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
            ->whereIn('orders.status', ['paid', 'shipped'])
            ->groupBy('orders.user_id', 'users.name', 'users.phone')
            ->orderByDesc('total_spent')
            ->get();

        return [
            'total_orders'  => $totalOrders,
            'total_revenue' => $totalRevenue,
            'status_counts' => [
                'pending'   => $statusCounts['pending']   ?? 0,
                'paid'      => $statusCounts['paid']      ?? 0,
                'shipped'   => $statusCounts['shipped']   ?? 0,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
            ],
            'last_7_days'   => $last7Days,
            'top_products'  => $topProducts,
            'top_customers' => $topCustomers,
        ];
    }
}
