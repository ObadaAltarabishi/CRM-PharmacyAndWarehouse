<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderCartItemRequest;
use App\Models\Order;
use App\Models\OrderCart;
use App\Models\OrderCartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WarehouseProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PharmacyOrderCartController extends Controller
{
    public function show(): JsonResponse
    {
        $pharmacy = request()->user();

        $cart = OrderCart::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart is empty.',
            ]);
        }

        $cart->load('items.product', 'warehouse:id,warehouse_name');

        return response()->json($this->cartResponse($cart));
    }

    public function addItem(StoreOrderCartItemRequest $request): JsonResponse
    {
        $pharmacy = $request->user();
        $data = $request->validated();

        $product = Product::query()
            ->where('barcode', $data['barcode'])
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found for barcode.',
                'barcode' => $data['barcode'],
            ], 404);
        }

        $warehouseProduct = WarehouseProduct::query()
            ->where('warehouse_id', $data['warehouse_id'])
            ->where('product_id', $product->id)
            ->first();

        if (!$warehouseProduct || $warehouseProduct->quantity < (int) $data['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock in warehouse.',
                'barcode' => $data['barcode'],
            ], 422);
        }

        $cart = OrderCart::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->first();

        if ($cart && (int) $cart->warehouse_id !== (int) $data['warehouse_id']) {
            return response()->json([
                'message' => 'Cart already linked to another warehouse.',
                'cart_warehouse_id' => $cart->warehouse_id,
            ], 422);
        }

        if (!$cart) {
            $cart = OrderCart::create([
                'pharmacy_id' => $pharmacy->id,
                'warehouse_id' => $data['warehouse_id'],
            ]);
        }

        $item = OrderCartItem::query()
            ->where('order_cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $newQuantity = $item->quantity + (int) $data['quantity'];
            if ($warehouseProduct->quantity < $newQuantity) {
                return response()->json([
                    'message' => 'Insufficient stock in warehouse.',
                    'barcode' => $data['barcode'],
                ], 422);
            }
            $item->quantity = $newQuantity;
            $item->save();
        } else {
            OrderCartItem::create([
                'order_cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => (int) $data['quantity'],
            ]);
        }

        $cart->load('items.product', 'warehouse:id,warehouse_name');

        return response()->json($this->cartResponse($cart), 201);
    }

    public function checkout(): JsonResponse
    {
        $pharmacy = request()->user();

        $cart = OrderCart::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $result = DB::transaction(function () use ($cart, $pharmacy) {
            $total = 0;

            foreach ($cart->items as $item) {
                $warehouseProduct = WarehouseProduct::query()
                    ->where('warehouse_id', $cart->warehouse_id)
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

            $order = Order::create([
                'pharmacy_id' => $pharmacy->id,
                'warehouse_id' => $cart->warehouse_id,
                'status' => Order::STATUS_PENDING,
                'total_cost' => 0,
            ]);

            foreach ($cart->items as $item) {
                $warehouseProduct = WarehouseProduct::query()
                    ->where('warehouse_id', $cart->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $unitCost = (float) $warehouseProduct->sell_price_to_pharmacy;
                $lineTotal = $unitCost * $item->quantity;
                $total += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);
            }

            $order->total_cost = $total;
            $order->save();

            OrderCartItem::query()
                ->where('order_cart_id', $cart->id)
                ->delete();

            $cart->delete();

            return [
                'ok' => true,
                'order' => $order,
            ];
        });

        if (!$result['ok']) {
            return response()->json([
                'message' => $result['message'],
                'product_id' => $result['product_id'] ?? null,
            ], 422);
        }

        $order = $result['order'];
        $order->load(['items.product', 'warehouse:id,warehouse_name']);

        return response()->json([
            'message' => 'Order created.',
            'order' => $order,
        ], 201);
    }

    private function cartResponse(OrderCart $cart): array
    {
        $items = [];
        $total = 0;

        foreach ($cart->items as $item) {
            $warehouseProduct = WarehouseProduct::query()
                ->where('warehouse_id', $cart->warehouse_id)
                ->where('product_id', $item->product_id)
                ->first();

            $unitPrice = $warehouseProduct ? (float) $warehouseProduct->sell_price_to_pharmacy : 0;
            $lineTotal = $unitPrice * $item->quantity;
            $total += $lineTotal;

            $items[] = [
                'barcode' => $item->product->barcode,
                'name' => $item->product->name,
                'strength' => $item->product->strength,
                'company_name' => $item->product->company_name,
                'form' => $item->product->form,
                'quantity' => $item->quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'cart_id' => $cart->id,
            'warehouse_id' => $cart->warehouse_id,
            'warehouse_name' => $cart->warehouse->warehouse_name ?? null,
            'items' => $items,
            'total' => $total,
        ];
    }
}
