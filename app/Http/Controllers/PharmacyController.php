<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePharmacyRequest;
use App\Http\Requests\DeletePharmacyRequest;
use App\Http\Requests\ListPharmaciesRequest;
use App\Models\Pharmacy;
use App\Models\Region;

class PharmacyController extends Controller
{
    // GET /api/pharmacies
    public function index(ListPharmaciesRequest $request)
    {
        $pharmacies = Pharmacy::query()
            ->with(['admin:id,name', 'region:id,name'])
            ->get();

        return response()->json($pharmacies);
    }

    // GET /api/pharmacies/count
    public function count(ListPharmaciesRequest $request)
    {
        return response()->json([
            'count' => Pharmacy::count(),
        ]);
    }

    // GET /api/regions/pharmacies-count
    public function countsByRegion(ListPharmaciesRequest $request)
    {
        $regions = Region::query()
            ->withCount('pharmacies')
            ->get(['id', 'name']);

        return response()->json($regions);
    }

    // POST /api/pharmacies
    public function store(CreatePharmacyRequest $request)
    {
        $data = $request->validated();
        $data['admin_id'] = $request->user()->id;

        $pharmacy = Pharmacy::create($data);

        return response()->json([
            'message' => 'Pharmacy created.',
            'pharmacy' => $pharmacy,
        ], 201);
    }

    // DELETE /api/pharmacies/{pharmacy}
    public function destroy(DeletePharmacyRequest $request, Pharmacy $pharmacy)
    {
        $pharmacy->delete();

        return response()->json([
            'message' => 'Pharmacy deleted.',
        ]);
    }
}
