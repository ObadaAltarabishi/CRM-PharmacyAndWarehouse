<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Feedback;
use App\Models\Pharmacy;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class AdminStatsController extends Controller
{
    public function summary(): JsonResponse
    {
        $topPharmaciesByOrders = Pharmacy::query()
            ->withCount('orders')
            ->orderByDesc('orders_count')
            ->orderBy('pharmacy_name')
            ->get(['id', 'pharmacy_name'])
            ->map(fn (Pharmacy $pharmacy) => [
                'pharmacy_id' => $pharmacy->id,
                'pharmacy_name' => $pharmacy->pharmacy_name,
                'orders_count' => (int) $pharmacy->orders_count,
            ])
            ->values();

        $topWarehousesByOrders = Warehouse::query()
            ->withCount('orders')
            ->orderByDesc('orders_count')
            ->orderBy('warehouse_name')
            ->get(['id', 'warehouse_name'])
            ->map(fn (Warehouse $warehouse) => [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->warehouse_name,
                'orders_count' => (int) $warehouse->orders_count,
            ])
            ->values();

        $topRatedWarehouses = Warehouse::query()
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->get(['id', 'warehouse_name'])
            ->filter(fn (Warehouse $warehouse) => $warehouse->ratings_count > 0)
            ->sortByDesc(fn (Warehouse $warehouse) => [
                round((float) $warehouse->ratings_avg_rating, 2),
                (int) $warehouse->ratings_count,
            ])
            ->map(fn (Warehouse $warehouse) => [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->warehouse_name,
                'rating_average' => round((float) $warehouse->ratings_avg_rating, 2),
                'ratings_count' => (int) $warehouse->ratings_count,
            ])
            ->values();

        return response()->json([
            'totals' => [
                'pharmacies_count' => Pharmacy::count(),
                'warehouses_count' => Warehouse::count(),
                'admins_count' => Admin::count(),
                'feedbacks_and_complaints_count' => Feedback::count(),
            ],
            'top_pharmacies_by_orders' => $topPharmaciesByOrders,
            'top_warehouses_by_orders' => $topWarehousesByOrders,
            'top_rated_warehouses' => $topRatedWarehouses,
        ]);
    }
}
