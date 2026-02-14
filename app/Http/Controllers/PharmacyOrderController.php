<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WarehouseProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PharmacyOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $pharmacy = request()->user();

        $orders = Order::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->with(['items.product', 'warehouse:id,warehouse_name'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $pharmacy = $request->user();
        $data = $request->validated();

        $itemsData = [];
        $total = 0;

        foreach ($data['items'] as $item) {
            $product = Product::query()
                ->where('barcode', $item['barcode'])
                ->first();

            if (!$product) {
                return response()->json([
                    'message' => 'Product not found for barcode.',
                    'barcode' => $item['barcode'],
                ], 404);
            }

            $warehouseProduct = WarehouseProduct::query()
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('product_id', $product->id)
                ->first();

            if (!$warehouseProduct) {
                return response()->json([
                    'message' => 'Product not available in warehouse.',
                    'barcode' => $item['barcode'],
                ], 422);
            }

            $unitCost = (float) $warehouseProduct->sell_price_to_pharmacy;
            $lineTotal = $unitCost * (int) $item['quantity'];
            $total += $lineTotal;

            $itemsData[] = [
                'product_id' => $product->id,
                'quantity' => (int) $item['quantity'],
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
            ];
        }

        $order = DB::transaction(function () use ($pharmacy, $data, $itemsData, $total) {
            $order = Order::create([
                'pharmacy_id' => $pharmacy->id,
                'warehouse_id' => $data['warehouse_id'],
                'status' => Order::STATUS_PENDING,
                'total_cost' => $total,
            ]);

            foreach ($itemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            return $order;
        });

        $order->load(['items.product', 'warehouse:id,warehouse_name']);

        return response()->json([
            'message' => 'Order created.',
            'order' => $order,
        ], 201);
    }
}
