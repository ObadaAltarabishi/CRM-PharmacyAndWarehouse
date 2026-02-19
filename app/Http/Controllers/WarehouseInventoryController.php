<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseProductRequest;
use App\Models\Product;
use App\Models\WarehouseProduct;
use App\Support\Pricing;
use Illuminate\Http\JsonResponse;

class WarehouseInventoryController extends Controller
{
    public function listByWarehouse(int $warehouseId): JsonResponse
    {
        $products = WarehouseProduct::query()
            ->where('warehouse_id', $warehouseId)
            ->with('product')
            ->get();

        $response = $products->map(function (WarehouseProduct $item) {
            $available = max(0, $item->quantity - $item->reserved_quantity);

            return [
                'id' => $item->id,
                'warehouse_id' => $item->warehouse_id,
                'product_id' => $item->product_id,
                'available_quantity' => $available,
                'sell_price_to_pharmacy' => $item->sell_price_to_pharmacy,
                'product' => $item->product,
            ];
        });

        return response()->json($response);
    }

    public function index(): JsonResponse
    {
        $warehouse = request()->user();

        $products = WarehouseProduct::query()
            ->where('warehouse_id', $warehouse->id)
            ->with('product')
            ->get();

        return response()->json($products);
    }

    public function destroy(string $barcode): JsonResponse
    {
        $warehouse = request()->user();

        $product = Product::query()
            ->where('barcode', $barcode)
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found for barcode.',
                'barcode' => $barcode,
            ], 404);
        }

        $record = WarehouseProduct::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Product not found in warehouse.',
                'barcode' => $barcode,
            ], 404);
        }

        $record->delete();

        return response()->json([
            'message' => 'Product removed from warehouse.',
        ]);
    }

    public function store(StoreWarehouseProductRequest $request): JsonResponse
    {
        $warehouse = $request->user();
        $data = $request->validated();

        $product = Product::query()
            ->where('barcode', $data['barcode'])
            ->first();

        if (!$product) {
            $missing = [];
            foreach (['name', 'strength', 'company_name'] as $field) {
                if (empty($data[$field])) {
                    $missing[] = $field;
                }
            }

            if (!empty($missing)) {
                return response()->json([
                    'message' => 'Missing product data.',
                    'missing_fields' => $missing,
                ], 422);
            }

            $product = Product::create([
                'barcode' => $data['barcode'],
                'name' => $data['name'],
                'strength' => $data['strength'],
                'company_name' => $data['company_name'],
                'form' => $data['form'] ?? null,
            ]);
        }

        $sellPrice = Pricing::applyMarkup((float) $data['cost_price']);

        $record = WarehouseProduct::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        if ($record) {
            $record->quantity += (int) $data['quantity'];
            $record->cost_price = $data['cost_price'];
            $record->sell_price_to_pharmacy = $sellPrice;
            $record->save();
        } else {
            $record = WarehouseProduct::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'quantity' => (int) $data['quantity'],
                'cost_price' => $data['cost_price'],
                'sell_price_to_pharmacy' => $sellPrice,
            ]);
        }

        $record->load('product');

        return response()->json([
            'message' => 'Warehouse product saved.',
            'warehouse_product' => $record,
        ], 201);
    }
}
