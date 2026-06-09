<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyOrderAssistantProposalRequest;
use App\Models\OrderCart;
use App\Models\OrderCartItem;
use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PharmacyOrderAssistantController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;
    private const LOW_STOCK_SUGGESTED_QUANTITY = 5;
    private const OUT_OF_STOCK_SUGGESTED_QUANTITY = 10;

    public function proposal(): JsonResponse
    {
        $pharmacy = request()->user();
        $items = $this->suggestedItems($pharmacy->id);

        if ($items->isEmpty()) {
            return response()->json([
                'message' => 'No products need ordering.',
                'items' => [],
            ]);
        }

        $proposal = $this->bestWarehouseProposal($items);

        if (!$proposal) {
            return response()->json([
                'message' => 'No single warehouse can fulfill all suggested items.',
                'items' => $items->values(),
            ], 422);
        }

        return response()->json($proposal);
    }

    public function apply(ApplyOrderAssistantProposalRequest $request): JsonResponse
    {
        $pharmacy = $request->user();
        $data = $request->validated();

        $result = DB::transaction(function () use ($pharmacy, $data) {
            $itemsData = [];
            $total = 0;

            foreach ($data['items'] as $item) {
                $product = Product::query()
                    ->where('barcode', $item['barcode'])
                    ->first();

                if (!$product) {
                    return [
                        'ok' => false,
                        'status' => 404,
                        'message' => 'Product not found for barcode.',
                        'barcode' => $item['barcode'],
                    ];
                }

                $warehouseProduct = WarehouseProduct::query()
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                $available = $warehouseProduct
                    ? max(0, $warehouseProduct->quantity - $warehouseProduct->reserved_quantity)
                    : 0;

                if (!$warehouseProduct || $available < (int) $item['quantity']) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Insufficient stock in warehouse.',
                        'barcode' => $item['barcode'],
                    ];
                }

                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $warehouseProduct->sell_price_to_pharmacy;
                $lineTotal = $unitPrice * $quantity;
                $total += $lineTotal;

                $itemsData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $cart = OrderCart::query()
                ->where('pharmacy_id', $pharmacy->id)
                ->lockForUpdate()
                ->first();

            if ($cart && (int) $cart->warehouse_id !== (int) $data['warehouse_id']) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'Cart already linked to another warehouse.',
                    'cart_warehouse_id' => $cart->warehouse_id,
                ];
            }

            if (!$cart) {
                $cart = OrderCart::create([
                    'pharmacy_id' => $pharmacy->id,
                    'warehouse_id' => $data['warehouse_id'],
                ]);
            }

            OrderCartItem::query()
                ->where('order_cart_id', $cart->id)
                ->delete();

            foreach ($itemsData as $itemData) {
                OrderCartItem::create([
                    'order_cart_id' => $cart->id,
                    'product_id' => $itemData['product']->id,
                    'quantity' => $itemData['quantity'],
                ]);
            }

            return [
                'ok' => true,
                'cart' => $cart,
                'total' => $total,
            ];
        });

        if (!$result['ok']) {
            $payload = [
                'message' => $result['message'],
            ];

            if (isset($result['barcode'])) {
                $payload['barcode'] = $result['barcode'];
            }

            if (isset($result['cart_warehouse_id'])) {
                $payload['cart_warehouse_id'] = $result['cart_warehouse_id'];
            }

            return response()->json($payload, $result['status']);
        }

        $cart = $result['cart'];
        $cart->load('items.product', 'warehouse:id,warehouse_name');

        return response()->json([
            'message' => 'Assistant proposal applied to order cart.',
            'cart' => $this->cartResponse($cart),
        ], 201);
    }

    private function suggestedItems(int $pharmacyId): Collection
    {
        return PharmacyProduct::query()
            ->where('pharmacy_id', $pharmacyId)
            ->where('quantity', '<', self::LOW_STOCK_THRESHOLD)
            ->with('product')
            ->get()
            ->map(function (PharmacyProduct $pharmacyProduct) {
                $reason = $pharmacyProduct->quantity === 0 ? 'out_of_stock' : 'low_stock';
                $suggestedQuantity = $reason === 'out_of_stock'
                    ? self::OUT_OF_STOCK_SUGGESTED_QUANTITY
                    : self::LOW_STOCK_SUGGESTED_QUANTITY;

                return [
                    'product_id' => $pharmacyProduct->product_id,
                    'barcode' => $pharmacyProduct->product->barcode,
                    'name' => $pharmacyProduct->product->name,
                    'strength' => $pharmacyProduct->product->strength,
                    'company_name' => $pharmacyProduct->product->company_name,
                    'form' => $pharmacyProduct->product->form,
                    'current_pharmacy_quantity' => $pharmacyProduct->quantity,
                    'suggested_quantity' => $suggestedQuantity,
                    'reason' => $reason,
                ];
            });
    }

    private function bestWarehouseProposal(Collection $items): ?array
    {
        $productIds = $items->pluck('product_id');
        $warehouses = Warehouse::query()
            ->with(['region:id,name'])
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->get();

        $bestProposal = null;

        foreach ($warehouses as $warehouse) {
            $warehouseProducts = WarehouseProduct::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

            if ($warehouseProducts->count() !== $items->count()) {
                continue;
            }

            $proposalItems = [];
            $total = 0;
            $canFulfill = true;

            foreach ($items as $item) {
                $warehouseProduct = $warehouseProducts[$item['product_id']] ?? null;
                $available = $warehouseProduct
                    ? max(0, $warehouseProduct->quantity - $warehouseProduct->reserved_quantity)
                    : 0;

                if (!$warehouseProduct || $available < $item['suggested_quantity']) {
                    $canFulfill = false;
                    break;
                }

                $unitPrice = (float) $warehouseProduct->sell_price_to_pharmacy;
                $lineTotal = $unitPrice * $item['suggested_quantity'];
                $total += $lineTotal;

                $proposalItems[] = array_merge($item, [
                    'available_quantity' => $available,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
            }

            if (!$canFulfill) {
                continue;
            }

            if ($bestProposal === null || $total < $bestProposal['total_cost']) {
                $bestProposal = [
                    'warehouse' => [
                        'id' => $warehouse->id,
                        'warehouse_name' => $warehouse->warehouse_name,
                        'region' => $warehouse->region,
                        'ratings_count' => (int) $warehouse->ratings_count,
                        'rating_average' => $warehouse->ratings_avg_rating !== null
                            ? round((float) $warehouse->ratings_avg_rating, 2)
                            : null,
                    ],
                    'items' => $proposalItems,
                    'total_cost' => $total,
                ];
            }
        }

        return $bestProposal;
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
