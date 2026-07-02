<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ExpenseInvoice;
use App\Models\WarehouseProduct;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WarehouseStatsController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $warehouse = request()->user();
        $periods = $this->periodStarts();
        $income = [
            'daily' => $this->income($warehouse->id, $periods['day']),
            'weekly' => $this->income($warehouse->id, $periods['week']),
            'monthly' => $this->income($warehouse->id, $periods['month']),
            'yearly' => $this->income($warehouse->id, $periods['year']),
        ];
        $expenses = [
            'daily' => $this->expenses($warehouse->id, $periods['day']),
            'weekly' => $this->expenses($warehouse->id, $periods['week']),
            'monthly' => $this->expenses($warehouse->id, $periods['month']),
            'yearly' => $this->expenses($warehouse->id, $periods['year']),
        ];

        return response()->json([
            'counts' => [
                'products_count' => WarehouseProduct::query()
                    ->where('warehouse_id', $warehouse->id)
                    ->count(),
                'available_products_count' => WarehouseProduct::query()
                    ->where('warehouse_id', $warehouse->id)
                    ->whereColumn('quantity', '>', 'reserved_quantity')
                    ->count(),
            ],
            'orders_by_status' => $this->ordersByStatus($warehouse->id),
            'income' => $income,
            'expenses' => $expenses,
            'net_profit' => [
                'daily' => $this->netProfit($warehouse->id, $periods['day']),
                'weekly' => $this->netProfit($warehouse->id, $periods['week']),
                'monthly' => $this->netProfit($warehouse->id, $periods['month']),
                'yearly' => $this->netProfit($warehouse->id, $periods['year']),
            ],
            'cash_balance' => $this->cashBalance($income, $expenses),
            'top_requested_products' => $this->topRequestedProducts($warehouse->id),
            'latest_orders' => $this->latestOrders($warehouse->id),
        ]);
    }

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

    private function periodStarts(): array
    {
        return [
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
        ];
    }

    private function ordersByStatus(int $warehouseId): array
    {
        $counts = Order::query()
            ->where('warehouse_id', $warehouseId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            Order::STATUS_PENDING => (int) ($counts[Order::STATUS_PENDING] ?? 0),
            Order::STATUS_APPROVED => (int) ($counts[Order::STATUS_APPROVED] ?? 0),
            Order::STATUS_REJECTED => (int) ($counts[Order::STATUS_REJECTED] ?? 0),
            Order::STATUS_RECEIVED => (int) ($counts[Order::STATUS_RECEIVED] ?? 0),
            Order::STATUS_ISSUE => (int) ($counts[Order::STATUS_ISSUE] ?? 0),
        ];
    }

    private function income(int $warehouseId, Carbon $from): float
    {
        return round((float) Order::query()
            ->where('warehouse_id', $warehouseId)
            ->where('status', Order::STATUS_RECEIVED)
            ->where('received_at', '>=', $from)
            ->sum('total_cost'), 4);
    }

    private function expenses(int $warehouseId, Carbon $from): float
    {
        return round((float) ExpenseInvoice::query()
            ->where('warehouse_id', $warehouseId)
            ->where('created_at', '>=', $from)
            ->sum('amount'), 4);
    }

    private function netProfit(int $warehouseId, Carbon $from): float
    {
        $orders = Order::query()
            ->where('warehouse_id', $warehouseId)
            ->where('status', Order::STATUS_RECEIVED)
            ->where('received_at', '>=', $from)
            ->with('items')
            ->get();

        $productIds = $orders
            ->flatMap(fn (Order $order) => $order->items->pluck('product_id'))
            ->unique()
            ->values();

        $currentCosts = WarehouseProduct::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->pluck('cost_price', 'product_id');

        $profit = $orders->sum(function (Order $order) use ($currentCosts) {
            return $order->items->sum(function (OrderItem $item) use ($currentCosts) {
                $sellPrice = (float) $item->unit_cost;
                $warehouseCost = $item->warehouse_unit_cost ?? $currentCosts[$item->product_id] ?? 0;

                return ($sellPrice - (float) $warehouseCost) * (int) $item->quantity;
            });
        });

        return round((float) $profit, 4);
    }

    private function cashBalance(array $income, array $expenses): array
    {
        return [
            'daily' => round($income['daily'] - $expenses['daily'], 4),
            'weekly' => round($income['weekly'] - $expenses['weekly'], 4),
            'monthly' => round($income['monthly'] - $expenses['monthly'], 4),
            'yearly' => round($income['yearly'] - $expenses['yearly'], 4),
        ];
    }

    private function topRequestedProducts(int $warehouseId): array
    {
        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.warehouse_id', $warehouseId)
            ->groupBy(
                'products.id',
                'products.name',
                'products.strength',
                'products.company_name',
                'products.form'
            )
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit(5)
            ->get([
                'products.id as product_id',
                'products.name',
                'products.strength',
                'products.company_name',
                'products.form',
                DB::raw('SUM(order_items.quantity) as requested_quantity'),
                DB::raw('SUM(order_items.line_total) as requested_total'),
            ])
            ->map(fn ($item) => [
                'product_id' => (int) $item->product_id,
                'name' => $item->name,
                'strength' => $item->strength,
                'company_name' => $item->company_name,
                'form' => $item->form,
                'requested_quantity' => (int) $item->requested_quantity,
                'requested_total' => round((float) $item->requested_total, 4),
            ])
            ->all();
    }

    private function latestOrders(int $warehouseId): array
    {
        return Order::query()
            ->where('warehouse_id', $warehouseId)
            ->with('pharmacy:id,pharmacy_name')
            ->latest()
            ->limit(2)
            ->get()
            ->map(fn (Order $order) => [
                'order_id' => $order->id,
                'pharmacy_id' => $order->pharmacy_id,
                'pharmacy_name' => $order->pharmacy?->pharmacy_name,
                'status' => $order->status,
                'total_cost' => (float) $order->total_cost,
                'created_at' => $order->created_at,
                'approved_at' => $order->approved_at,
                'received_at' => $order->received_at,
                'rejected_at' => $order->rejected_at,
                'issue_at' => $order->issue_at,
            ])
            ->all();
    }
}
