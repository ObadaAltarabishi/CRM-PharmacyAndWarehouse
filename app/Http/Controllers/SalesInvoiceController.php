<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesInvoiceRequest;
use App\Http\Requests\UpdateSalesInvoiceRequest;
use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function withFeedback(): JsonResponse
    {
        $pharmacy = request()->user();

        $invoices = SalesInvoice::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->whereNotNull('feedback')
            ->with(['items.product'])
            ->latest()
            ->get();

        return response()->json($invoices);
    }

    public function showWithFeedback(SalesInvoice $salesInvoice): JsonResponse
    {
        $pharmacy = request()->user();

        if ($salesInvoice->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($salesInvoice->feedback === null) {
            return response()->json(['message' => 'Invoice has no feedback.'], 404);
        }

        $salesInvoice->load(['items.product']);

        return response()->json($salesInvoice);
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

    public function update(UpdateSalesInvoiceRequest $request, SalesInvoice $salesInvoice): JsonResponse
    {
        $pharmacy = $request->user();

        if ($salesInvoice->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validated();

        if (array_key_exists('paid_total', $data)) {
            $paidTotal = (float) $data['paid_total'];
            $totalPrice = (float) $salesInvoice->total_price;
            $discountPercent = $totalPrice > 0 ? (($totalPrice - $paidTotal) / $totalPrice) * 100 : 0;

            $salesInvoice->paid_total = $paidTotal;
            $salesInvoice->discount_percent = $discountPercent;
        }

        if (array_key_exists('feedback', $data)) {
            $salesInvoice->feedback = $data['feedback'];
        }

        $salesInvoice->save();

        return response()->json([
            'message' => 'Sales invoice updated.',
            'sales_invoice' => $salesInvoice,
        ]);
    }

    public function updatePaidTotal(Request $request, SalesInvoice $salesInvoice): JsonResponse
    {
        $pharmacy = $request->user();

        if ($salesInvoice->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'paid_total' => ['required', 'numeric', 'min:0'],
        ]);

        $paidTotal = (float) $data['paid_total'];
        $totalPrice = (float) $salesInvoice->total_price;
        $discountPercent = $totalPrice > 0 ? (($totalPrice - $paidTotal) / $totalPrice) * 100 : 0;

        $salesInvoice->paid_total = $paidTotal;
        $salesInvoice->discount_percent = $discountPercent;
        $salesInvoice->save();

        return response()->json([
            'message' => 'Paid total updated.',
            'sales_invoice' => $salesInvoice,
        ]);
    }

    public function clearFeedback(SalesInvoice $salesInvoice): JsonResponse
    {
        $pharmacy = request()->user();

        if ($salesInvoice->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $salesInvoice->feedback = null;
        $salesInvoice->save();

        return response()->json([
            'message' => 'Feedback cleared.',
            'sales_invoice' => $salesInvoice,
        ]);
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
