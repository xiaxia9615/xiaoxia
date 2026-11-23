<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    /**
     * Dashboard 聚合统计：总用户数、今日订单数、今日交易额（毫）、
     * 商品总数、待发货数、已支付订单数。
     */
    public function apiStats(): JsonResponse
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $totalUsers = (int) User::query()->count();

        $todayOrders = (int) Order::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        // 今日交易额：已支付订单的 amount 之和（单位：毫）
        $todayRevenue = (int) Order::query()
            ->whereIn('status', [
                OrderStatus::Paid,
                OrderStatus::PendingShipment,
                OrderStatus::Completed,
            ])
            ->whereBetween('paid_at', [$todayStart, $todayEnd])
            ->sum('amount');

        $totalProducts = (int) Product::query()->count();

        $pendingShipment = (int) Order::query()
            ->where('status', OrderStatus::PendingShipment)
            ->count();

        $paidOrders = (int) Order::query()
            ->whereIn('status', [
                OrderStatus::Paid,
                OrderStatus::PendingShipment,
                OrderStatus::Completed,
            ])
            ->count();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'total_users' => $totalUsers,
                'today_orders' => $todayOrders,
                'today_revenue' => $todayRevenue,
                'total_products' => $totalProducts,
                'pending_shipment' => $pendingShipment,
                'paid_orders' => $paidOrders,
            ],
        ]);
    }
}
