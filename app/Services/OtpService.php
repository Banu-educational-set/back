<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const PURPOSE_LOGIN = 'login';
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public function __construct(private readonly SmsService $sms) {}

    /**
     * Generate an OTP for the given phone and purpose, persist a hash,
     * and dispatch it via SMS. Throws if the resend cooldown is active.
     */
    public function issue(string $phone, string $purpose): void
    {
        $cooldown = (int) config('services.otp.resend_cooldown_seconds', 60);

        $latest = DB::table('otp_codes')
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($latest && Carbon::parse($latest->created_at)->addSeconds($cooldown)->isFuture()) {
            throw ValidationException::withMessages([
                'phone' => ['Please wait before requesting another code.'],
            ]);
        }

        $code = $this->generateCode();
        $ttl = (int) config('services.otp.ttl_seconds', 120);

        DB::table('otp_codes')->insert([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addSeconds($ttl),
            'consumed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sms->send($phone, "Your verification code: {$code}", $code);
    }

    /**
     * Verify the given OTP for the phone+purpose pair. Consumes the latest
     * matching active row on success. Throws ValidationException otherwise.
     *
     * Development convenience: when services.otp.master_code is set, any
     * caller can pass that value as the code and verification is short-
     * circuited to success. The latest matching row (if any) is consumed
     * so the bypass leaves the table in the same state a normal success
     * would. Set OTP_MASTER_CODE to empty in production to disable.
     */
    public function verify(string $phone, string $purpose, string $code): void
    {
        $masterCode = (string) config('services.otp.master_code', '');
        if ($masterCode !== '' && hash_equals($masterCode, $code)) {
            DB::table('otp_codes')
                ->where('phone', $phone)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            return;
        }

        $maxAttempts = (int) config('services.otp.max_attempts', 5);

        $row = DB::table('otp_codes')
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $row) {
            $this->failVerification();
        }

        if (Carbon::parse($row->expires_at)->isPast()) {
            $this->failVerification('Code has expired.');
        }

        if ($row->attempts >= $maxAttempts) {
            DB::table('otp_codes')->where('id', $row->id)->update([
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);
            $this->failVerification('Too many attempts. Request a new code.');
        }

        if (! Hash::check($code, $row->code_hash)) {
            DB::table('otp_codes')->where('id', $row->id)->update([
                'attempts' => $row->attempts + 1,
                'updated_at' => now(),
            ]);
            $this->failVerification();
        }

        DB::table('otp_codes')->where('id', $row->id)->update([
            'consumed_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function generateCode(): string
    {
        $length = max(4, min(10, (int) config('services.otp.length', 6)));
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function failVerification(string $message = 'Invalid or expired code.'): never
    {
        throw ValidationException::withMessages(['code' => [$message]]);
    }
}
