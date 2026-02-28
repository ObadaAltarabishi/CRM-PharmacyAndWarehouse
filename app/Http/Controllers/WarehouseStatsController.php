<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ExpenseInvoice;
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

        $expenseDaily = ExpenseInvoice::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereDate('created_at', $today)
            ->sum('amount');
        $expenseWeekly = ExpenseInvoice::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('created_at', '>=', $weekStart)
            ->sum('amount');
        $expenseMonthly = ExpenseInvoice::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        return response()->json([
            'daily_sales' => $daily,
            'weekly_sales' => $weekly,
            'monthly_sales' => $monthly,
            'daily_expenses' => $expenseDaily,
            'weekly_expenses' => $expenseWeekly,
            'monthly_expenses' => $expenseMonthly,
            'daily_net' => $daily - $expenseDaily,
            'weekly_net' => $weekly - $expenseWeekly,
            'monthly_net' => $monthly - $expenseMonthly,
        ]);
    }
}
