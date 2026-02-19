<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PharmacyProduct;
use App\Models\WarehouseProduct;
use App\Support\Pricing;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WarehouseOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $warehouse = request()->user();
        $status = request()->query('status');

        $query = Order::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['items.product', 'pharmacy:id,pharmacy_name', 'feedbacks']);

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->latest()->get();

        return response()->json($orders);
    }

    public function issues(): JsonResponse
    {
        $warehouse = request()->user();

        $orders = Order::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereHas('feedbacks')
            ->with(['items.product', 'pharmacy:id,pharmacy_name,doctor_phone,doctor_email', 'feedbacks'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function approve(Order $order): JsonResponse
    {
        $warehouse = request()->user();

        if ($order->warehouse_id !== $warehouse->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json(['message' => 'Order already processed.'], 422);
        }

        $order->status = Order::STATUS_APPROVED;
        $order->approved_at = Carbon::now();
        $order->save();

        $order->load(['items.product', 'pharmacy:id,pharmacy_name']);

        return response()->json([
            'message' => 'Order approved.',
            'order' => $order,
        ]);
    }

    public function reject(Order $order): JsonResponse
    {
        $warehouse = request()->user();

        if ($order->warehouse_id !== $warehouse->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json(['message' => 'Order already processed.'], 422);
        }

        $order->load('items');

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $warehouseProduct = WarehouseProduct::query()
                    ->where('warehouse_id', $order->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($warehouseProduct) {
                    $warehouseProduct->reserved_quantity = max(
                        0,
                        $warehouseProduct->reserved_quantity - $item->quantity
                    );
                    $warehouseProduct->save();
                }
            }
        });

        $order->status = Order::STATUS_REJECTED;
        $order->rejected_at = Carbon::now();
        $order->save();

        return response()->json([
            'message' => 'Order rejected.',
            'order' => $order,
        ]);
    }
}
