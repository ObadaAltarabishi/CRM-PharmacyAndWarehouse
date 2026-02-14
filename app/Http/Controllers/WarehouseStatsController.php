<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class WarehouseStatsController extends Controller
{
    public function summary(): JsonResponse
    {
        $warehouse = request()->user();
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $baseQuery = Order::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('status', Order::STATUS_APPROVED);

        $daily = (clone $baseQuery)
            ->whereDate('approved_at', $today)
            ->sum('total_cost');

        $weekly = (clone $baseQuery)
            ->where('approved_at', '>=', $weekStart)
            ->sum('total_cost');

        $monthly = (clone $baseQuery)
            ->where('approved_at', '>=', $monthStart)
            ->sum('total_cost');

        return response()->json([
            'daily_sales' => $daily,
            'weekly_sales' => $weekly,
            'monthly_sales' => $monthly,
        ]);
    }
}
