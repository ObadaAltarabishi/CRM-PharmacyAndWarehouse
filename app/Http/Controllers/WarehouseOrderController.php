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
            ->with(['items.product', 'pharmacy:id,pharmacy_name']);

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->latest()->get();

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

        $order->load('items');

        $result = DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $warehouseProduct = WarehouseProduct::query()
                    ->where('warehouse_id', $order->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$warehouseProduct || $warehouseProduct->quantity < $item->quantity) {
                    return [
                        'ok' => false,
                        'message' => 'Insufficient stock in warehouse.',
                        'product_id' => $item->product_id,
                    ];
                }
            }

            foreach ($order->items as $item) {
                $warehouseProduct = WarehouseProduct::query()
                    ->where('warehouse_id', $order->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $warehouseProduct->quantity -= $item->quantity;
                $warehouseProduct->save();

                $pharmacyProduct = PharmacyProduct::query()
                    ->where('pharmacy_id', $order->pharmacy_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $defaultSellPrice = Pricing::applyMarkup((float) $item->unit_cost);

                if ($pharmacyProduct) {
                    $pharmacyProduct->quantity += $item->quantity;
                    $pharmacyProduct->cost_price = $item->unit_cost;
                    $pharmacyProduct->default_sell_price = $defaultSellPrice;
                    $pharmacyProduct->save();
                } else {
                    PharmacyProduct::create([
                        'pharmacy_id' => $order->pharmacy_id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'cost_price' => $item->unit_cost,
                        'default_sell_price' => $defaultSellPrice,
                    ]);
                }
            }

            $order->status = Order::STATUS_APPROVED;
            $order->approved_at = Carbon::now();
            $order->save();

            return ['ok' => true];
        });

        if (!$result['ok']) {
            return response()->json([
                'message' => $result['message'],
                'product_id' => $result['product_id'] ?? null,
            ], 422);
        }

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

        $order->status = Order::STATUS_REJECTED;
        $order->rejected_at = Carbon::now();
        $order->save();

        return response()->json([
            'message' => 'Order rejected.',
            'order' => $order,
        ]);
    }
}
