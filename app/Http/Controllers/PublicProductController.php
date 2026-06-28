<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicProductSearchRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class PublicProductController extends Controller
{
    public function index(PublicProductSearchRequest $request): JsonResponse
    {
        $query = trim($request->validated('query'));
        $terms = collect(preg_split('/\s+/', $query))
            ->filter()
            ->values();

        $products = Product::query()
            ->select(['id', 'name', 'strength', 'company_name', 'form'])
            ->where(function ($builder) use ($query, $terms) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('strength', 'like', "%{$query}%")
                    ->orWhere('company_name', 'like', "%{$query}%")
                    ->orWhere('form', 'like', "%{$query}%");

                foreach ($terms as $term) {
                    $builder->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('strength', 'like', "%{$term}%")
                        ->orWhere('company_name', 'like', "%{$term}%")
                        ->orWhere('form', 'like', "%{$term}%");
                }
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'query' => $query,
            'data' => $products,
        ]);
    }
}
