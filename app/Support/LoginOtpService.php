<?php

namespace App\Support;

use App\Mail\LoginOtpMail;
use App\Models\LoginOtp;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LoginOtpService
{
    private const EXPIRES_IN_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 30;

    public function issue(Model $actor, string $email, string $actorLabel): LoginOtp
    {
        $this->expireActiveOtps($actor);

        return $this->createAndSend($actor, $email, $actorLabel);
    }

    public function resend(Model $actor, string $email, string $actorLabel): array
    {
        $otp = $this->activeOtp($actor);

        if (!$otp) {
            $this->createAndSend($actor, $email, $actorLabel);

            return ['ok' => true];
        }

        if ($otp->last_sent_at && $otp->last_sent_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            return [
                'ok' => false,
                'message' => 'Please wait before requesting another OTP.',
                'retry_after_seconds' => self::RESEND_COOLDOWN_SECONDS - $otp->last_sent_at->diffInSeconds(now()),
            ];
        }

        $code = $this->generateCode();

        $otp->code_hash = Hash::make($code);
        $otp->attempts = 0;
        $otp->resend_count++;
        $otp->last_sent_at = now();
        $otp->expires_at = now()->addMinutes(self::EXPIRES_IN_MINUTES);
        $otp->save();

        Mail::to($email)->send(new LoginOtpMail($code, $actorLabel));

        return ['ok' => true];
    }

    public function resendByToken(string $actorClass, string $requestToken, string $actorLabel): array
    {
        $otp = LoginOtp::query()
            ->where('authenticatable_type', $actorClass)
            ->where('request_token', $requestToken)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return [
                'ok' => false,
                'message' => 'Invalid or expired OTP request.',
                'status' => 422,
            ];
        }

        if ($otp->last_sent_at && $otp->last_sent_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            return [
                'ok' => false,
                'message' => 'Please wait before requesting another OTP.',
                'retry_after_seconds' => self::RESEND_COOLDOWN_SECONDS - $otp->last_sent_at->diffInSeconds(now()),
                'status' => 429,
            ];
        }

        $code = $this->generateCode();

        $otp->code_hash = Hash::make($code);
        $otp->attempts = 0;
        $otp->resend_count++;
        $otp->last_sent_at = now();
        $otp->expires_at = now()->addMinutes(self::EXPIRES_IN_MINUTES);
        $otp->save();

        Mail::to($otp->email)->send(new LoginOtpMail($code, $actorLabel));

        return [
            'ok' => true,
            'otp' => $otp,
        ];
    }

    public function verify(Model $actor, string $code): array
    {
        $otp = $this->activeOtp($actor);

        if (!$otp) {
            return [
                'ok' => false,
                'message' => 'Invalid or expired OTP.',
                'status' => 422,
            ];
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->used_at = now();
            $otp->save();

            return [
                'ok' => false,
                'message' => 'Too many invalid OTP attempts. Please login again.',
                'status' => 429,
            ];
        }

        if (!Hash::check($code, $otp->code_hash)) {
            $otp->attempts++;

            if ($otp->attempts >= self::MAX_ATTEMPTS) {
                $otp->used_at = now();
            }

            $otp->save();

            return [
                'ok' => false,
                'message' => $otp->attempts >= self::MAX_ATTEMPTS
                    ? 'Too many invalid OTP attempts. Please login again.'
                    : 'Invalid OTP.',
                'status' => $otp->attempts >= self::MAX_ATTEMPTS ? 429 : 422,
            ];
        }

        $otp->used_at = now();
        $otp->save();

        return ['ok' => true];
    }

    public function verifyForType(string $actorClass, string $code): array
    {
        $otps = LoginOtp::query()
            ->where('authenticatable_type', $actorClass)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        foreach ($otps as $otp) {
            if (!Hash::check($code, $otp->code_hash)) {
                continue;
            }

            try {
                $actor = $actorClass::query()->findOrFail($otp->authenticatable_id);
            } catch (ModelNotFoundException) {
                $otp->used_at = now();
                $otp->save();

                return [
                    'ok' => false,
                    'message' => 'Invalid or expired OTP.',
                    'status' => 422,
                ];
            }

            $result = $this->verify($actor, $code);

            if (!$result['ok']) {
                return $result;
            }

            return [
                'ok' => true,
                'actor' => $actor,
            ];
        }

        return $this->recordInvalidAttemptForLatestOtp($actorClass);
    }

    private function createAndSend(Model $actor, string $email, string $actorLabel): LoginOtp
    {
        $code = $this->generateCode();

        $otp = LoginOtp::create([
            'authenticatable_type' => $actor::class,
            'authenticatable_id' => $actor->getKey(),
            'email' => $email,
            'request_token' => Str::random(64),
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'resend_count' => 0,
            'last_sent_at' => now(),
            'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
        ]);

        Mail::to($email)->send(new LoginOtpMail($code, $actorLabel));

        return $otp;
    }

    private function expireActiveOtps(Model $actor): void
    {
        LoginOtp::query()
            ->where('authenticatable_type', $actor::class)
            ->where('authenticatable_id', $actor->getKey())
            ->whereNull('used_at')
            ->update(['used_at' => Carbon::now()]);
    }

    private function activeOtp(Model $actor): ?LoginOtp
    {
        return LoginOtp::query()
            ->where('authenticatable_type', $actor::class)
            ->where('authenticatable_id', $actor->getKey())
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    private function recordInvalidAttemptForLatestOtp(string $actorClass): array
    {
        $otp = LoginOtp::query()
            ->where('authenticatable_type', $actorClass)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return [
                'ok' => false,
                'message' => 'Invalid or expired OTP.',
                'status' => 422,
            ];
        }

        $otp->attempts++;

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->used_at = now();
        }

        $otp->save();

        return [
            'ok' => false,
            'message' => $otp->attempts >= self::MAX_ATTEMPTS
                ? 'Too many invalid OTP attempts. Please login again.'
                : 'Invalid OTP.',
            'status' => $otp->attempts >= self::MAX_ATTEMPTS ? 429 : 422,
        ];
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
