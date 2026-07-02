<?php

namespace Modules\AuthManagement\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Shared phone-OTP mechanics over the `vito_otps` table.
 *
 * Mirrors the flow already proven in ClientOtpAuthController (5-min expiry,
 * 30s resend cooldown, 5-attempt lock, bcrypt-hashed codes) so the Vito
 * forgot-PIN recovery reuses the exact same rules. ClientOtpAuth3 keeps its
 * own copy intentionally to avoid changing that tested login path.
 */
trait HandlesPhoneOtp
{
    /** True when an unverified OTP was issued to this phone in the last 30 seconds. */
    protected function otpResendCooldownActive(string $phone): bool
    {
        return DB::table('vito_otps')
            ->where('phone', $phone)
            ->where('created_at', '>', now()->subSeconds(30))
            ->whereNull('verified_at')
            ->exists();
    }

    /** Generate + store a 6-digit OTP, dispatch the SMS, and return the raw code. */
    protected function issuePhoneOtp(string $phone): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('vito_otps')->insert([
            'id'         => (string) Str::uuid(),
            'phone'      => $phone,
            'otp_hash'   => Hash::make($otp),
            'expires_at' => now()->addMinutes(5)->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->dispatchOtpSms($phone, $otp);

        return $otp;
    }

    /**
     * Verify (and consume) the most recent live OTP for a phone.
     * Returns ['ok' => bool, 'status' => int, 'message' => string]; on failure
     * `status` is the HTTP code to return (404/422/400), matching the OTP-login flow.
     */
    protected function verifyPhoneOtp(string $phone, string $otp): array
    {
        $record = DB::table('vito_otps')
            ->where('phone', $phone)
            ->where('expires_at', '>', now()->toDateTimeString())
            ->whereNull('verified_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$record) {
            return ['ok' => false, 'status' => 404, 'message' => 'OTP has expired or was not found. Please request a new one.'];
        }

        if ($record->attempts >= 5) {
            return ['ok' => false, 'status' => 422, 'message' => 'Too many failed attempts. Please request a new OTP.'];
        }

        DB::table('vito_otps')->where('id', $record->id)->increment('attempts');

        if (!Hash::check($otp, $record->otp_hash)) {
            return ['ok' => false, 'status' => 400, 'message' => 'Invalid OTP. Please try again.'];
        }

        DB::table('vito_otps')
            ->where('id', $record->id)
            ->update(['verified_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString()]);

        return ['ok' => true, 'status' => 200, 'message' => 'OK'];
    }

    /**
     * Send the OTP: prefer the admin-configured SMS gateway, fall back to direct
     * Twilio env vars, and finally log-only (dev/testing). Never throws.
     */
    protected function dispatchOtpSms(string $phone, string $otp): void
    {
        try {
            $status = \Modules\BusinessManagement\Lib\SMSGateway::send($phone, $otp);
            if ($status === 'success') {
                return;
            }
        } catch (\Throwable $e) {
            Log::warning('Configured SMS gateway failed: ' . $e->getMessage());
        }

        $message = "Your VITO verification code is: {$otp}";
        try {
            $sid   = env('TWILIO_ACCOUNT_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $from  = env('TWILIO_FROM_NUMBER');

            if ($sid && $token && $from) {
                $client = new \Twilio\Rest\Client($sid, $token);
                retry(3, fn() => $client->messages->create($phone, ['from' => $from, 'body' => $message]), 1000);
                return;
            }
        } catch (\Throwable $e) {
            Log::warning('Twilio env SMS dispatch failed: ' . $e->getMessage());
        }

        Log::info("OTP SMS to {$phone}: {$message}");
    }
}
