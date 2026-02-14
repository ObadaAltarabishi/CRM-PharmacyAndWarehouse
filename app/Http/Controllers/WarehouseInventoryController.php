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

        return response()->json($products);
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
            return response()->json([
                'message' => 'Product not found for barcode.',
                'barcode' => $data['barcode'],
            ], 404);
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
