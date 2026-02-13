<?php

namespace App\Http\Controllers;

use App\Http\Requests\PharmacyLoginRequest;
use App\Models\Pharmacy;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PharmacyAuthController extends Controller
{
    public function login(PharmacyLoginRequest $request)
    {
        $data = $request->validated();

        $pharmacy = Pharmacy::where('doctor_email', $data['login'])
            ->orWhere('doctor_phone', $data['login'])
            ->first();

        if (!$pharmacy || !Hash::check($data['password'], $pharmacy->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        $token = $pharmacy->createToken('pharmacy-token', ['pharmacy'])->plainTextToken;

        return response()->json([
            'pharmacy' => $pharmacy,
            'token' => $token,
        ]);
    }
}
