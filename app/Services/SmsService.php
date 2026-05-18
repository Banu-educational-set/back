<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): void
    {
        $driver = (string) config('services.sms.driver', 'log');

        match ($driver) {
            'kavenegar' => $this->sendKavenegar($phone, $message),
            default => $this->sendLog($phone, $message),
        };
    }

    private function sendLog(string $phone, string $message): void
    {
        Log::info('SMS (log driver)', ['phone' => $phone, 'message' => $message]);
    }

    private function sendKavenegar(string $phone, string $message): void
    {
        // Placeholder for a real provider integration. Falls back to log if
        // credentials are missing so dev/test flows don't break.
        $apiKey = (string) config('services.sms.kavenegar.api_key');

        if ($apiKey === '') {
            $this->sendLog($phone, $message);

            return;
        }

        // Intentionally not implemented: integrate the real HTTP client here.
        $this->sendLog($phone, $message);
    }
}
