<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function showByBarcode(Request $request, string $barcode): JsonResponse
    {
        $product = Product::query()
            ->where('barcode', $barcode)
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json($product);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        $product = Product::query()
            ->where('barcode', $data['barcode'])
            ->first();

        if ($product) {
            return response()->json([
                'message' => 'Product already exists.',
                'product' => $product,
            ]);
        }

        $product = Product::create($data);

        return response()->json([
            'message' => 'Product created.',
            'product' => $product,
        ], 201);
    }
}
