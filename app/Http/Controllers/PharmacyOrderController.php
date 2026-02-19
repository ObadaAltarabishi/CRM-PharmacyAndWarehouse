<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderIssueRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\WarehouseProduct;
use App\Support\Pricing;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PharmacyOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $pharmacy = request()->user();

        $orders = Order::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->with(['items.product', 'warehouse:id,warehouse_name', 'feedbacks'])
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

        $order = DB::transaction(function () use ($pharmacy, $data, &$itemsData, &$total) {
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
                    ->lockForUpdate()
                    ->first();

                if (!$warehouseProduct) {
                    return response()->json([
                        'message' => 'Product not available in warehouse.',
                        'barcode' => $item['barcode'],
                    ], 422);
                }

                $available = max(0, $warehouseProduct->quantity - $warehouseProduct->reserved_quantity);
                if ($available < (int) $item['quantity']) {
                    return response()->json([
                        'message' => 'Insufficient stock in warehouse.',
                        'barcode' => $item['barcode'],
                    ], 422);
                }

                $warehouseProduct->reserved_quantity += (int) $item['quantity'];
                $warehouseProduct->save();

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

        if ($order instanceof JsonResponse) {
            return $order;
        }

        $order->load(['items.product', 'warehouse:id,warehouse_name']);

        return response()->json([
            'message' => 'Order created.',
            'order' => $order,
        ], 201);
    }

    public function receive(Order $order): JsonResponse
    {
        $pharmacy = request()->user();

        if ($order->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (!in_array($order->status, [Order::STATUS_APPROVED, Order::STATUS_ISSUE], true)) {
            return response()->json(['message' => 'Order is not ready to receive.'], 422);
        }

        $order->load('items');

        $result = DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $warehouseProduct = WarehouseProduct::query()
                    ->where('warehouse_id', $order->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$warehouseProduct || $warehouseProduct->reserved_quantity < $item->quantity) {
                    return [
                        'ok' => false,
                        'message' => 'Reserved stock not available.',
                        'product_id' => $item->product_id,
                    ];
                }

                if ($warehouseProduct->quantity < $item->quantity) {
                    return [
                        'ok' => false,
                        'message' => 'Warehouse quantity insufficient.',
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
                $warehouseProduct->reserved_quantity = max(
                    0,
                    $warehouseProduct->reserved_quantity - $item->quantity
                );
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

            $order->status = Order::STATUS_RECEIVED;
            $order->received_at = Carbon::now();
            $order->save();

            return ['ok' => true];
        });

        if (!$result['ok']) {
            return response()->json([
                'message' => $result['message'],
                'product_id' => $result['product_id'] ?? null,
            ], 422);
        }

        $order->load(['items.product', 'warehouse:id,warehouse_name', 'feedbacks']);

        return response()->json([
            'message' => 'Order received.',
            'order' => $order,
        ]);
    }

    public function issue(Order $order, StoreOrderIssueRequest $request): JsonResponse
    {
        $pharmacy = $request->user();

        if ($order->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (!in_array($order->status, [Order::STATUS_APPROVED, Order::STATUS_ISSUE], true)) {
            return response()->json(['message' => 'Order is not ready for issue reporting.'], 422);
        }

        $content = $request->validated()['content'];

        Feedback::create([
            'content' => $content,
            'pharmacy_id' => $pharmacy->id,
            'warehouse_id' => $order->warehouse_id,
            'order_id' => $order->id,
        ]);

        $order->status = Order::STATUS_ISSUE;
        $order->issue_at = Carbon::now();
        $order->issue_note = $content;
        $order->save();

        $order->load(['items.product', 'warehouse:id,warehouse_name', 'feedbacks']);

        return response()->json([
            'message' => 'Issue reported.',
            'order' => $order,
        ]);
    }
}
