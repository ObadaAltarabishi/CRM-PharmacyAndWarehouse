<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\AdminRegisterRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function register(AdminRegisterRequest $request)
    {
        $data = $request->validated();

        // أول أدمن دايمًا super_admin، غير هيك admin
        $isFirstAdmin = Admin::count() === 0;
        $data['role'] = $isFirstAdmin ? 'super_admin' : 'admin';

        $admin = Admin::create($data);

        $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

        return response()->json([
            'admin' => $admin,
            'token' => $token,
        ], 201);
    }

    public function login(AdminLoginRequest $request)
    {
        $data = $request->validated();

        $admin = Admin::where('email', $data['login'])
            ->orWhere('phone', $data['login'])
            ->first();

        if (!$admin || !Hash::check($data['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

        return response()->json([
            'admin' => $admin,
            'token' => $token,
        ]);
    }
}
