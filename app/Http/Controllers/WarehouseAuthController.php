<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResendLoginOtpRequest;
use App\Http\Requests\VerifyLoginOtpRequest;
use App\Http\Requests\WarehouseLoginRequest;
use App\Models\Warehouse;
use App\Support\LoginOtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WarehouseAuthController extends Controller
{
    public function login(WarehouseLoginRequest $request, LoginOtpService $otpService)
    {
        $warehouse = $this->validateCredentials($request->validated());

        $otp = $otpService->issue($warehouse, $warehouse->owner_email, 'warehouse');

        return response()->json([
            'message' => 'OTP sent to your email.',
            'requires_otp' => true,
            'otp_request_token' => $otp->request_token,
        ]);
    }

    public function verifyOtp(VerifyLoginOtpRequest $request, LoginOtpService $otpService)
    {
        $result = $otpService->verifyForType(Warehouse::class, $request->validated()['otp']);

        if (!$result['ok']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['status']);
        }

        $warehouse = $result['actor'];
        $token = $warehouse->createToken('warehouse-token', ['warehouse'])->plainTextToken;

        return response()->json([
            'warehouse' => $warehouse,
            'token' => $token,
        ]);
    }

    public function resendOtp(ResendLoginOtpRequest $request, LoginOtpService $otpService)
    {
        $result = $otpService->resendByToken(
            Warehouse::class,
            $request->validated()['otp_request_token'],
            'warehouse'
        );

        if (!$result['ok']) {
            return response()->json([
                'message' => $result['message'],
                'retry_after_seconds' => $result['retry_after_seconds'] ?? null,
            ], $result['status']);
        }

        return response()->json([
            'message' => 'OTP sent to your email.',
            'requires_otp' => true,
            'otp_request_token' => $result['otp']->request_token,
        ]);
    }

    private function validateCredentials(array $data): Warehouse
    {
        $warehouse = $this->findByLogin($data['login']);

        if (!$warehouse || !Hash::check($data['password'], $warehouse->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        return $warehouse;
    }

    private function findByLogin(string $login): ?Warehouse
    {
        return Warehouse::where('owner_email', $login)
            ->orWhere('owner_phone', $login)
            ->first();
    }
}
