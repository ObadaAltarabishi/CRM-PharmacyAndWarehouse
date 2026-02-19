<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesInvoiceRequest;
use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $pharmacy = request()->user();

        $invoices = SalesInvoice::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->with(['items.product'])
            ->latest()
            ->get();

        return response()->json($invoices);
    }

    public function show(SalesInvoice $salesInvoice): JsonResponse
    {
        $pharmacy = request()->user();

        if ($salesInvoice->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $salesInvoice->load(['items.product']);

        return response()->json($salesInvoice);
    }

    public function store(StoreSalesInvoiceRequest $request): JsonResponse
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

            $pharmacyProduct = PharmacyProduct::query()
                ->where('pharmacy_id', $pharmacy->id)
                ->where('product_id', $product->id)
                ->first();

            if (!$pharmacyProduct || $pharmacyProduct->quantity < (int) $item['quantity']) {
                return response()->json([
                    'message' => 'Insufficient stock in pharmacy.',
                    'barcode' => $item['barcode'],
                ], 422);
            }

            $unitPrice = (float) $item['unit_price'];
            $lineTotal = $unitPrice * (int) $item['quantity'];
            $total += $lineTotal;

            $itemsData[] = [
                'product_id' => $product->id,
                'quantity' => (int) $item['quantity'],
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        $invoice = DB::transaction(function () use ($pharmacy, $itemsData, $total) {
            $invoice = SalesInvoice::create([
                'pharmacy_id' => $pharmacy->id,
                'total_price' => $total,
            ]);

            foreach ($itemsData as $itemData) {
                $itemData['sales_invoice_id'] = $invoice->id;
                SalesInvoiceItem::create($itemData);

                $pharmacyProduct = PharmacyProduct::query()
                    ->where('pharmacy_id', $pharmacy->id)
                    ->where('product_id', $itemData['product_id'])
                    ->lockForUpdate()
                    ->first();

                $pharmacyProduct->quantity -= $itemData['quantity'];
                $pharmacyProduct->save();
            }

            return $invoice;
        });

        $invoice->load(['items.product']);

        return response()->json([
            'message' => 'Sale recorded.',
            'invoice' => $invoice,
        ], 201);
    }
}
