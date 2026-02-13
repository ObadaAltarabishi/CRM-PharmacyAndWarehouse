<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseLoginRequest;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WarehouseAuthController extends Controller
{
    public function login(WarehouseLoginRequest $request)
    {
        $data = $request->validated();

        $warehouse = Warehouse::where('owner_email', $data['login'])
            ->orWhere('owner_phone', $data['login'])
            ->first();

        if (!$warehouse || !Hash::check($data['password'], $warehouse->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        $token = $warehouse->createToken('warehouse-token', ['warehouse'])->plainTextToken;

        return response()->json([
            'warehouse' => $warehouse,
            'token' => $token,
        ]);
    }
}
