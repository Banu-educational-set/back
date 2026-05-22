<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function __construct(private readonly OtpService $otp) {}

    /**
     * Register a new student account and issue an access token.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
        ]);

        $user->assignRole(RoleName::Student->value);

        $token = $user->createToken($data['device_name'] ?? 'api')->plainTextToken;

        return ['user' => $user->fresh('roles'), 'token' => $token];
    }

    /**
     * Authenticate a user by credentials and issue a fresh token.
     *
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     */
    public function login(array $credentials): array
    {
        $user = User::where('phone', $credentials['phone'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api')->plainTextToken;

        return ['user' => $user->load('roles'), 'token' => $token];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        // Stateful (cookie) sessions return TransientToken which can't be deleted;
        // only delete real PersonalAccessTokens.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Send an OTP for passwordless login. Silent if the phone is unknown so we
     * don't leak which numbers are registered.
     */
    public function requestLoginOtp(string $phone): void
    {
        if (! User::where('phone', $phone)->exists()) {
            return;
        }

        $this->otp->issue($phone, OtpService::PURPOSE_LOGIN);
    }

    /**
     * Verify a login OTP and issue a Sanctum token.
     *
     * @return array{user: User, token: string}
     */
    public function loginWithOtp(string $phone, string $code, ?string $deviceName = null): array
    {
        $this->otp->verify($phone, OtpService::PURPOSE_LOGIN, $code);

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $token = $user->createToken($deviceName ?? 'api')->plainTextToken;

        return ['user' => $user->load('roles'), 'token' => $token];
    }

    /**
     * Send an OTP for password reset. Silent if the phone is unknown.
     */
    public function requestPasswordResetOtp(string $phone): void
    {
        if (! User::where('phone', $phone)->exists()) {
            return;
        }

        $this->otp->issue($phone, OtpService::PURPOSE_PASSWORD_RESET);
    }

    /**
     * Verify a password-reset OTP and return a single-use reset token. The
     * token must be supplied to resetPassword() within the configured TTL.
     */
    public function verifyPasswordResetOtp(string $phone, string $code): string
    {
        $this->otp->verify($phone, OtpService::PURPOSE_PASSWORD_RESET, $code);

        if (! User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => ['Account not found.']]);
        }

        $token = Str::random(64);
        $ttl = (int) config('services.otp.password_reset_token_ttl_seconds', 600);

        DB::table('phone_password_reset_tokens')->insert([
            'phone' => $phone,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addSeconds($ttl),
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $token;
    }

    /**
     * Consume a phone reset token and update the user's password.
     */
    public function resetPassword(string $phone, string $token, string $newPassword): void
    {
        $tokenHash = hash('sha256', $token);

        $row = DB::table('phone_password_reset_tokens')
            ->where('phone', $phone)
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $row || Carbon::parse($row->expires_at)->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired reset token.'],
            ]);
        }

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            throw ValidationException::withMessages(['phone' => ['Account not found.']]);
        }

        DB::transaction(function () use ($row, $user, $newPassword) {
            $user->forceFill(['password' => $newPassword])->save();

            DB::table('phone_password_reset_tokens')
                ->where('id', $row->id)
                ->update(['used_at' => now(), 'updated_at' => now()]);

            // Invalidate any other outstanding reset tokens for this phone.
            DB::table('phone_password_reset_tokens')
                ->where('phone', $row->phone)
                ->where('id', '!=', $row->id)
                ->whereNull('used_at')
                ->update(['used_at' => now(), 'updated_at' => now()]);
        });
    }
}
