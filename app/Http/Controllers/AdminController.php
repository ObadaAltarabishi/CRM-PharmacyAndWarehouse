<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAdminRequest;
use App\Http\Requests\ListAdminsCountByRegionRequest;
use App\Http\Requests\ListAdminsPharmacyCountRequest;
use App\Http\Requests\ListAdminsWarehouseCountRequest;
use App\Http\Requests\PromoteAdminRequest;
use App\Models\Admin;
use App\Models\Region;
use App\Models\Pharmacy;
use App\Models\Warehouse;

class AdminController extends Controller
{
    // GET /api/admins
    public function index()
    {
        $admins = Admin::query()
            ->with('region:id,name')   // يجيب اسم المنطقة
            ->get();

        return response()->json($admins);
    }

    // GET /api/admins/count
    public function count()
    {
        return response()->json([
            'count' => Admin::count()
        ]);
    }

    // GET /api/admins/pharmacies-count
    public function pharmaciesCount(ListAdminsPharmacyCountRequest $request)
    {
        $admins = Admin::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->withCount('pharmacies')
            ->get(['id', 'name']);

        $response = $admins->map(function (Admin $admin) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'pharmacies_count' => $admin->pharmacies_count,
            ];
        });

        return response()->json($response);
    }

    // GET /api/admins/warehouses-count
    public function warehousesCount(ListAdminsWarehouseCountRequest $request)
    {
        $admins = Admin::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->withCount('warehouse')
            ->get(['id', 'name']);

        $response = $admins->map(function (Admin $admin) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'warehouses_count' => $admin->warehouse_count,
            ];
        });

        return response()->json($response);
    }

    // GET /api/regions/admins-count
    public function adminsCountByRegion(ListAdminsCountByRegionRequest $request)
    {
        $regions = Region::query()
            ->withCount('admins')
            ->get(['id', 'name']);

        return response()->json($regions);
    }

    // PATCH /api/admins/{admin}/make-super-admin
    public function makeSuperAdmin(PromoteAdminRequest $request, Admin $admin)
    {
        if ($admin->role === 'super_admin') {
            return response()->json([
                'message' => 'Admin is already a super admin.',
                'admin' => $admin,
            ]);
        }

        $admin->update(['role' => 'super_admin']);

        return response()->json([
            'message' => 'Admin promoted to super admin.',
            'admin' => $admin,
        ]);
    }

    // DELETE /api/admins/{admin}
    public function destroy(DeleteAdminRequest $request, Admin $admin)
    {
        if ($admin->role === 'super_admin') {
            return response()->json([
                'message' => 'Cannot delete a super admin.',
            ], 403);
        }

        $admin->delete();

        return response()->json([
            'message' => 'Admin deleted.',
            'admin' => $admin,
        ]);
    }
}
