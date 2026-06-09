<?php

namespace App\Http\Controllers;

use App\Http\Requests\PharmacyLoginRequest;
use App\Http\Requests\ResendLoginOtpRequest;
use App\Http\Requests\VerifyLoginOtpRequest;
use App\Models\Pharmacy;
use App\Support\LoginOtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PharmacyAuthController extends Controller
{
    public function login(PharmacyLoginRequest $request, LoginOtpService $otpService)
    {
        $pharmacy = $this->validateCredentials($request->validated());

        $otp = $otpService->issue($pharmacy, $pharmacy->doctor_email, 'pharmacy');

        return response()->json([
            'message' => 'OTP sent to your email.',
            'requires_otp' => true,
            'otp_request_token' => $otp->request_token,
        ]);
    }

    public function verifyOtp(VerifyLoginOtpRequest $request, LoginOtpService $otpService)
    {
        $result = $otpService->verifyForType(Pharmacy::class, $request->validated()['otp']);

        if (!$result['ok']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['status']);
        }

        $pharmacy = $result['actor'];
        $token = $pharmacy->createToken('pharmacy-token', ['pharmacy'])->plainTextToken;

        return response()->json([
            'pharmacy' => $pharmacy,
            'token' => $token,
        ]);
    }

    public function resendOtp(ResendLoginOtpRequest $request, LoginOtpService $otpService)
    {
        $result = $otpService->resendByToken(
            Pharmacy::class,
            $request->validated()['otp_request_token'],
            'pharmacy'
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

    private function validateCredentials(array $data): Pharmacy
    {
        $pharmacy = $this->findByLogin($data['login']);

        if (!$pharmacy || !Hash::check($data['password'], $pharmacy->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        return $pharmacy;
    }

    private function findByLogin(string $login): ?Pharmacy
    {
        return Pharmacy::where('doctor_email', $login)
            ->orWhere('doctor_phone', $login)
            ->first();
    }
}
