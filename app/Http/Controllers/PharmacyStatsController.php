<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ExpenseInvoice;
use App\Models\PharmacyProduct;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PharmacyStatsController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $pharmacy = request()->user();
        $periods = $this->periodStarts();
        $grossIncome = [
            'daily' => $this->grossIncome($pharmacy->id, $periods['day']),
            'weekly' => $this->grossIncome($pharmacy->id, $periods['week']),
            'monthly' => $this->grossIncome($pharmacy->id, $periods['month']),
            'yearly' => $this->grossIncome($pharmacy->id, $periods['year']),
        ];
        $expenses = [
            'daily' => $this->expenses($pharmacy->id, $periods['day']),
            'weekly' => $this->expenses($pharmacy->id, $periods['week']),
            'monthly' => $this->expenses($pharmacy->id, $periods['month']),
            'yearly' => $this->expenses($pharmacy->id, $periods['year']),
        ];
        $purchasesForCashBalance = [
            'daily' => $this->purchases($pharmacy->id, $periods['day']),
            'weekly' => $this->purchases($pharmacy->id, $periods['week']),
            'monthly' => $this->purchases($pharmacy->id, $periods['month']),
            'yearly' => $this->purchases($pharmacy->id, $periods['year']),
        ];

        return response()->json([
            'counts' => [
                'inventory_products_count' => PharmacyProduct::query()
                    ->where('pharmacy_id', $pharmacy->id)
                    ->count(),
                'available_inventory_products_count' => PharmacyProduct::query()
                    ->where('pharmacy_id', $pharmacy->id)
                    ->where('quantity', '>', 0)
                    ->count(),
                'sales_invoices_count' => SalesInvoice::query()
                    ->where('pharmacy_id', $pharmacy->id)
                    ->count(),
            ],
            'orders_by_status' => $this->ordersByStatus($pharmacy->id),
            'gross_income' => $grossIncome,
            'net_income' => [
                'daily' => $this->netIncome($pharmacy->id, $periods['day']),
                'weekly' => $this->netIncome($pharmacy->id, $periods['week']),
                'monthly' => $this->netIncome($pharmacy->id, $periods['month']),
                'yearly' => $this->netIncome($pharmacy->id, $periods['year']),
            ],
            'expenses' => $expenses,
            'purchases' => [
                'weekly' => $purchasesForCashBalance['weekly'],
                'monthly' => $purchasesForCashBalance['monthly'],
                'yearly' => $purchasesForCashBalance['yearly'],
            ],
            'cash_balance' => $this->cashBalance($grossIncome, $expenses, $purchasesForCashBalance),
            'top_selling_products' => $this->topSellingProducts($pharmacy->id),
            'latest_orders' => $this->latestOrders($pharmacy->id),
            'latest_sales_invoices' => $this->latestSalesInvoices($pharmacy->id),
        ]);
    }

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

        $purchaseDaily = (clone $expensesBase)
            ->whereDate('approved_at', $today)
            ->sum('total_cost');
        $purchaseWeekly = (clone $expensesBase)
            ->where('approved_at', '>=', $weekStart)
            ->sum('total_cost');
        $purchaseMonthly = (clone $expensesBase)
            ->where('approved_at', '>=', $monthStart)
            ->sum('total_cost');

        $expenseDaily = ExpenseInvoice::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->whereDate('created_at', $today)
            ->sum('amount');
        $expenseWeekly = ExpenseInvoice::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('created_at', '>=', $weekStart)
            ->sum('amount');
        $expenseMonthly = ExpenseInvoice::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        $dailyExpenses = $purchaseDaily + $expenseDaily;
        $weeklyExpenses = $purchaseWeekly + $expenseWeekly;
        $monthlyExpenses = $purchaseMonthly + $expenseMonthly;

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

    private function periodStarts(): array
    {
        return [
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
        ];
    }

    private function ordersByStatus(int $pharmacyId): array
    {
        $counts = Order::query()
            ->where('pharmacy_id', $pharmacyId)
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

    private function grossIncome(int $pharmacyId, Carbon $from): float
    {
        return round((float) SalesInvoice::query()
            ->where('pharmacy_id', $pharmacyId)
            ->where('created_at', '>=', $from)
            ->sum(DB::raw('COALESCE(paid_total, total_price)')), 4);
    }

    private function netIncome(int $pharmacyId, Carbon $from): float
    {
        $invoices = SalesInvoice::query()
            ->where('pharmacy_id', $pharmacyId)
            ->where('created_at', '>=', $from)
            ->with('items')
            ->get();

        $productIds = $invoices
            ->flatMap(fn (SalesInvoice $invoice) => $invoice->items->pluck('product_id'))
            ->unique()
            ->values();

        $currentCosts = PharmacyProduct::query()
            ->where('pharmacy_id', $pharmacyId)
            ->whereIn('product_id', $productIds)
            ->pluck('cost_price', 'product_id');

        $net = $invoices->sum(function (SalesInvoice $invoice) use ($currentCosts) {
            $paidTotal = (float) ($invoice->paid_total ?? $invoice->total_price);
            $costTotal = $invoice->items->sum(function (SalesInvoiceItem $item) use ($currentCosts) {
                $unitCost = $item->unit_cost ?? $currentCosts[$item->product_id] ?? 0;

                return (float) $unitCost * (int) $item->quantity;
            });

            return $paidTotal - $costTotal;
        });

        return round((float) $net, 4);
    }

    private function expenses(int $pharmacyId, Carbon $from): float
    {
        return round((float) ExpenseInvoice::query()
            ->where('pharmacy_id', $pharmacyId)
            ->where('created_at', '>=', $from)
            ->sum('amount'), 4);
    }

    private function purchases(int $pharmacyId, Carbon $from): float
    {
        return round((float) Order::query()
            ->where('pharmacy_id', $pharmacyId)
            ->where('status', Order::STATUS_RECEIVED)
            ->where('received_at', '>=', $from)
            ->sum('total_cost'), 4);
    }

    private function cashBalance(array $grossIncome, array $expenses, array $purchases): array
    {
        return [
            'daily' => round($grossIncome['daily'] - $expenses['daily'] - $purchases['daily'], 4),
            'weekly' => round($grossIncome['weekly'] - $expenses['weekly'] - $purchases['weekly'], 4),
            'monthly' => round($grossIncome['monthly'] - $expenses['monthly'] - $purchases['monthly'], 4),
            'yearly' => round($grossIncome['yearly'] - $expenses['yearly'] - $purchases['yearly'], 4),
        ];
    }

    private function topSellingProducts(int $pharmacyId): array
    {
        return SalesInvoiceItem::query()
            ->join('sales_invoices', 'sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
            ->join('products', 'sales_invoice_items.product_id', '=', 'products.id')
            ->where('sales_invoices.pharmacy_id', $pharmacyId)
            ->groupBy(
                'products.id',
                'products.name',
                'products.strength',
                'products.company_name',
                'products.form'
            )
            ->orderByDesc(DB::raw('SUM(sales_invoice_items.quantity)'))
            ->limit(5)
            ->get([
                'products.id as product_id',
                'products.name',
                'products.strength',
                'products.company_name',
                'products.form',
                DB::raw('SUM(sales_invoice_items.quantity) as sold_quantity'),
                DB::raw('SUM(sales_invoice_items.line_total) as sales_total'),
            ])
            ->map(fn ($item) => [
                'product_id' => (int) $item->product_id,
                'name' => $item->name,
                'strength' => $item->strength,
                'company_name' => $item->company_name,
                'form' => $item->form,
                'sold_quantity' => (int) $item->sold_quantity,
                'sales_total' => round((float) $item->sales_total, 4),
            ])
            ->all();
    }

    private function latestOrders(int $pharmacyId): array
    {
        return Order::query()
            ->where('pharmacy_id', $pharmacyId)
            ->with('warehouse:id,warehouse_name')
            ->latest()
            ->limit(2)
            ->get()
            ->map(fn (Order $order) => [
                'order_id' => $order->id,
                'warehouse_id' => $order->warehouse_id,
                'warehouse_name' => $order->warehouse?->warehouse_name,
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

    private function latestSalesInvoices(int $pharmacyId): array
    {
        return SalesInvoice::query()
            ->where('pharmacy_id', $pharmacyId)
            ->latest()
            ->limit(2)
            ->get()
            ->map(fn (SalesInvoice $invoice) => [
                'sales_invoice_id' => $invoice->id,
                'total_price' => (float) $invoice->total_price,
                'paid_total' => (float) ($invoice->paid_total ?? $invoice->total_price),
                'discount_percent' => $invoice->discount_percent !== null
                    ? (float) $invoice->discount_percent
                    : null,
                'created_at' => $invoice->created_at,
            ])
            ->all();
    }
}
