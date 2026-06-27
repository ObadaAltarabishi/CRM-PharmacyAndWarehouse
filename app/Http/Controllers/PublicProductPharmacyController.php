<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicProductPharmaciesRequest;
use App\Models\PharmacyProduct;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class PublicProductPharmacyController extends Controller
{
    public function index(PublicProductPharmaciesRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $userLatitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $userLongitude = isset($data['longitude']) ? (float) $data['longitude'] : null;
        $shouldCalculateDistance = $userLatitude !== null && $userLongitude !== null;

        $availability = PharmacyProduct::query()
            ->with(['pharmacy:id,pharmacy_name,latitude,longitude'])
            ->where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function (PharmacyProduct $pharmacyProduct) use ($shouldCalculateDistance, $userLatitude, $userLongitude) {
                $pharmacy = $pharmacyProduct->pharmacy;
                $distanceKm = null;

                if ($shouldCalculateDistance && $pharmacy->latitude !== null && $pharmacy->longitude !== null) {
                    $distanceKm = $this->distanceInKilometers(
                        $userLatitude,
                        $userLongitude,
                        (float) $pharmacy->latitude,
                        (float) $pharmacy->longitude
                    );
                }

                return [
                    'pharmacy_id' => $pharmacy->id,
                    'pharmacy_name' => $pharmacy->pharmacy_name,
                    'quantity' => (int) $pharmacyProduct->quantity,
                    'price' => (float) $pharmacyProduct->default_sell_price,
                    'latitude' => $pharmacy->latitude,
                    'longitude' => $pharmacy->longitude,
                    'distance_km' => $distanceKm,
                ];
            });

        if ($shouldCalculateDistance) {
            $availability = $availability
                ->sortBy(fn (array $item) => $item['distance_km'] ?? PHP_FLOAT_MAX)
                ->values();
        }

        return response()->json([
            'product_id' => $product->id,
            'data' => $availability,
        ]);
    }

    private function distanceInKilometers(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude))
            * cos(deg2rad($toLatitude))
            * sin($longitudeDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }
}
