<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutSalesCartRequest;
use App\Http\Requests\StoreSalesCartItemRequest;
use App\Http\Requests\UpdateSalesCartItemQuantityRequest;
use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\SalesCart;
use App\Models\SalesCartItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PharmacySalesCartController extends Controller
{
    public function show(): JsonResponse
    {
        $pharmacy = request()->user();

        $cart = SalesCart::query()
            ->firstOrCreate(['pharmacy_id' => $pharmacy->id]);

        $cart->load('items.product');

        return response()->json($this->cartResponse($pharmacy->id, $cart));
    }

    public function addItem(StoreSalesCartItemRequest $request): JsonResponse
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

        $pharmacyProduct = PharmacyProduct::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$pharmacyProduct) {
            return response()->json([
                'message' => 'Insufficient stock in pharmacy.',
                'barcode' => $data['barcode'],
            ], 422);
        }

        $cart = SalesCart::query()
            ->firstOrCreate(['pharmacy_id' => $pharmacy->id]);

        $item = SalesCartItem::query()
            ->where('sales_cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $newQuantity = $item->quantity + (int) $data['quantity'];
            if ($pharmacyProduct->quantity < $newQuantity) {
                return response()->json([
                    'message' => 'Insufficient stock in pharmacy.',
                    'barcode' => $data['barcode'],
                ], 422);
            }

            $item->quantity = $newQuantity;
            $item->save();
        } else {
            if ($pharmacyProduct->quantity < (int) $data['quantity']) {
                return response()->json([
                    'message' => 'Insufficient stock in pharmacy.',
                    'barcode' => $data['barcode'],
                ], 422);
            }

            SalesCartItem::create([
                'sales_cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => (int) $data['quantity'],
            ]);
        }

        $cart->load('items.product');

        return response()->json($this->cartResponse($pharmacy->id, $cart), 201);
    }

    public function checkout(CheckoutSalesCartRequest $request): JsonResponse
    {
        $pharmacy = $request->user();
        $data = $request->validated();

        $cart = SalesCart::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $pricesByBarcode = [];
        foreach ($data['items'] as $item) {
            $pricesByBarcode[$item['barcode']] = (float) $item['unit_price'];
        }

        foreach ($cart->items as $cartItem) {
            $barcode = $cartItem->product->barcode;
            if (!array_key_exists($barcode, $pricesByBarcode)) {
                return response()->json([
                    'message' => 'Missing price for barcode.',
                    'barcode' => $barcode,
                ], 422);
            }
        }

        try {
            $invoice = DB::transaction(function () use ($pharmacy, $cart, $pricesByBarcode) {
            $total = 0;
            $invoice = SalesInvoice::create([
                'pharmacy_id' => $pharmacy->id,
                'total_price' => 0,
            ]);

            foreach ($cart->items as $cartItem) {
                $barcode = $cartItem->product->barcode;
                $unitPrice = $pricesByBarcode[$barcode];
                $lineTotal = $unitPrice * $cartItem->quantity;
                $total += $lineTotal;

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $pharmacyProduct = PharmacyProduct::query()
                    ->where('pharmacy_id', $pharmacy->id)
                    ->where('product_id', $cartItem->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pharmacyProduct || $pharmacyProduct->quantity < $cartItem->quantity) {
                    throw new \RuntimeException('Insufficient stock during checkout.');
                }

                $pharmacyProduct->quantity -= $cartItem->quantity;
                $pharmacyProduct->save();
            }

            $invoice->total_price = $total;
            $invoice->save();

            SalesCartItem::query()
                ->where('sales_cart_id', $cart->id)
                ->delete();

            return $invoice;
        });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $invoice->load('items.product');

        return response()->json([
            'message' => 'Sale recorded.',
            'invoice' => $invoice,
        ], 201);
    }

    public function removeItem(string $barcode): JsonResponse
    {
        $pharmacy = request()->user();

        $cart = SalesCart::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $product = Product::query()
            ->where('barcode', $barcode)
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found for barcode.',
                'barcode' => $barcode,
            ], 404);
        }

        $item = SalesCartItem::query()
            ->where('sales_cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'Item not found in cart.',
                'barcode' => $barcode,
            ], 404);
        }

        $item->delete();

        $cart->load('items.product');

        return response()->json($this->cartResponse($pharmacy->id, $cart));
    }

    public function updateQuantity(string $barcode, UpdateSalesCartItemQuantityRequest $request): JsonResponse
    {
        $pharmacy = $request->user();
        $data = $request->validated();

        $cart = SalesCart::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $product = Product::query()
            ->where('barcode', $barcode)
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found for barcode.',
                'barcode' => $barcode,
            ], 404);
        }

        $item = SalesCartItem::query()
            ->where('sales_cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'Item not found in cart.',
                'barcode' => $barcode,
            ], 404);
        }

        $pharmacyProduct = PharmacyProduct::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$pharmacyProduct || $pharmacyProduct->quantity < (int) $data['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock in pharmacy.',
                'barcode' => $barcode,
            ], 422);
        }

        $item->quantity = (int) $data['quantity'];
        $item->save();

        $cart->load('items.product');

        return response()->json($this->cartResponse($pharmacy->id, $cart));
    }

    private function cartResponse(int $pharmacyId, SalesCart $cart): array
    {
        $items = [];
        $total = 0;

        foreach ($cart->items as $item) {
            $pharmacyProduct = PharmacyProduct::query()
                ->where('pharmacy_id', $pharmacyId)
                ->where('product_id', $item->product_id)
                ->first();

            $defaultSellPrice = $pharmacyProduct ? (float) $pharmacyProduct->default_sell_price : 0;
            $lineTotal = $defaultSellPrice * $item->quantity;
            $total += $lineTotal;

            $items[] = [
                'barcode' => $item->product->barcode,
                'name' => $item->product->name,
                'strength' => $item->product->strength,
                'company_name' => $item->product->company_name,
                'form' => $item->product->form,
                'quantity' => $item->quantity,
                'default_unit_price' => $defaultSellPrice,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'cart_id' => $cart->id,
            'items' => $items,
            'total' => $total,
        ];
    }
}
