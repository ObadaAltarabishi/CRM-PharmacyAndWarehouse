<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PharmacyStatsController extends Controller
{
    public function summary(): JsonResponse
    {
        $pharmacy = request()->user();
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $salesBase = SalesInvoice::query()
            ->where('pharmacy_id', $pharmacy->id);

        $expensesBase = Order::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('status', Order::STATUS_APPROVED);

        $dailyRevenue = (clone $salesBase)
            ->whereDate('created_at', $today)
            ->sum('total_price');
        $weeklyRevenue = (clone $salesBase)
            ->where('created_at', '>=', $weekStart)
            ->sum('total_price');
        $monthlyRevenue = (clone $salesBase)
            ->where('created_at', '>=', $monthStart)
            ->sum('total_price');

        $dailyExpenses = (clone $expensesBase)
            ->whereDate('approved_at', $today)
            ->sum('total_cost');
        $weeklyExpenses = (clone $expensesBase)
            ->where('approved_at', '>=', $weekStart)
            ->sum('total_cost');
        $monthlyExpenses = (clone $expensesBase)
            ->where('approved_at', '>=', $monthStart)
            ->sum('total_cost');

        return response()->json([
            'daily' => [
                'revenue' => $dailyRevenue,
                'expenses' => $dailyExpenses,
                'profit' => $dailyRevenue - $dailyExpenses,
            ],
            'weekly' => [
                'revenue' => $weeklyRevenue,
                'expenses' => $weeklyExpenses,
                'profit' => $weeklyRevenue - $weeklyExpenses,
            ],
            'monthly' => [
                'revenue' => $monthlyRevenue,
                'expenses' => $monthlyExpenses,
                'profit' => $monthlyRevenue - $monthlyExpenses,
            ],
        ]);
    }
}
