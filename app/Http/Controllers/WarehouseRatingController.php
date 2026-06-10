<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRatingRequest;
use App\Models\Order;
use App\Models\Warehouse;
use App\Models\WarehouseRating;
use Illuminate\Http\JsonResponse;

class WarehouseRatingController extends Controller
{
    public function show(Warehouse $warehouse): JsonResponse
    {
        $ratingsCount = WarehouseRating::query()
            ->where('warehouse_id', $warehouse->id)
            ->count();
        $ratingAverage = WarehouseRating::query()
            ->where('warehouse_id', $warehouse->id)
            ->avg('rating');

        return response()->json([
            'warehouse_id' => $warehouse->id,
            'warehouse_name' => $warehouse->warehouse_name,
            'ratings_count' => $ratingsCount,
            'rating_average' => $ratingAverage !== null ? round((float) $ratingAverage, 2) : null,
        ]);
    }

    public function store(StoreWarehouseRatingRequest $request, Warehouse $warehouse): JsonResponse
    {
        $pharmacy = $request->user();
        $data = $request->validated();

        $hasReceivedOrder = Order::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('status', Order::STATUS_RECEIVED)
            ->exists();

        if (!$hasReceivedOrder) {
            return response()->json([
                'message' => 'You can rate only warehouses you have received orders from.',
            ], 403);
        }

        $rating = WarehouseRating::updateOrCreate(
            [
                'pharmacy_id' => $pharmacy->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'rating' => (int) $data['rating'],
            ]
        );

        $ratingsCount = WarehouseRating::query()
            ->where('warehouse_id', $warehouse->id)
            ->count();
        $ratingAverage = WarehouseRating::query()
            ->where('warehouse_id', $warehouse->id)
            ->avg('rating');

        return response()->json([
            'message' => $rating->wasRecentlyCreated ? 'Warehouse rated.' : 'Warehouse rating updated.',
            'rating' => [
                'warehouse_id' => $warehouse->id,
                'my_rating' => $rating->rating,
                'ratings_count' => $ratingsCount,
                'rating_average' => $ratingAverage !== null ? round((float) $ratingAverage, 2) : null,
            ],
        ], $rating->wasRecentlyCreated ? 201 : 200);
    }
}
