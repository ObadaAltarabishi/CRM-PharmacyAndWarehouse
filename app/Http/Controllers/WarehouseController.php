<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateWarehouseRequest;
use App\Http\Requests\DeleteWarehouseRequest;
use App\Http\Requests\ListWarehousesRequest;
use App\Models\Region;
use App\Models\Warehouse;

class WarehouseController extends Controller
{
    // GET /api/warehouses
    public function index(ListWarehousesRequest $request)
    {
        $warehouses = Warehouse::query()
            ->with(['admin:id,name', 'region:id,name'])
            ->get();

        return response()->json($warehouses);
    }

    // GET /api/warehouses/count
    public function count(ListWarehousesRequest $request)
    {
        return response()->json([
            'count' => Warehouse::count(),
        ]);
    }

    // GET /api/regions/warehouses-count
    public function countsByRegion(ListWarehousesRequest $request)
    {
        $regions = Region::query()
            ->withCount('warehouses')
            ->get(['id', 'name']);

        return response()->json($regions);
    }

    // POST /api/warehouses
    public function store(CreateWarehouseRequest $request)
    {
        $data = $request->validated();
        $data['admin_id'] = $request->user()->id;

        $warehouse = Warehouse::create($data);

        return response()->json([
            'message' => 'Warehouse created.',
            'warehouse' => $warehouse,
        ], 201);
    }

    // DELETE /api/warehouses/{warehouse}
    public function destroy(DeleteWarehouseRequest $request, Warehouse $warehouse)
    {
        $warehouse->delete();

        return response()->json([
            'message' => 'Warehouse deleted.',
        ]);
    }
}
