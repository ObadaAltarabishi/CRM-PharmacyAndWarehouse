<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePharmacyProductRequest;
use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Support\Pricing;
use Illuminate\Http\JsonResponse;

class PharmacyInventoryController extends Controller
{
    public function index(): JsonResponse
    {
        $pharmacy = request()->user();

        $products = PharmacyProduct::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->with('product')
            ->get();

        return response()->json($products);
    }

    public function destroy(string $barcode): JsonResponse
    {
        $pharmacy = request()->user();

        $product = Product::query()
            ->where('barcode', $barcode)
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found for barcode.',
                'barcode' => $barcode,
            ], 404);
        }

        $record = PharmacyProduct::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Product not found in pharmacy.',
                'barcode' => $barcode,
            ], 404);
        }

        $record->delete();

        return response()->json([
            'message' => 'Product removed from pharmacy.',
        ]);
    }

    public function store(StorePharmacyProductRequest $request): JsonResponse
    {
        $pharmacy = $request->user();
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

        $defaultSellPrice = Pricing::applyMarkup((float) $data['cost_price']);

        $record = PharmacyProduct::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('product_id', $product->id)
            ->first();

        if ($record) {
            $record->quantity += (int) $data['quantity'];
            $record->cost_price = $data['cost_price'];
            $record->default_sell_price = $defaultSellPrice;
            $record->save();
        } else {
            $record = PharmacyProduct::create([
                'pharmacy_id' => $pharmacy->id,
                'product_id' => $product->id,
                'quantity' => (int) $data['quantity'],
                'cost_price' => $data['cost_price'],
                'default_sell_price' => $defaultSellPrice,
            ]);
        }

        $record->load('product');

        return response()->json([
            'message' => 'Pharmacy product saved.',
            'pharmacy_product' => $record,
        ], 201);
    }
}
