<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Dispatch a plain SMS. The "code" argument is optional and only used
     * by template-based providers (e.g. sms.ir's /v1/send/verify). When the
     * driver supports templated verifications, supplying $code lets it
     * leverage the dedicated verification template; otherwise the raw
     * $message is the source of truth.
     */
    public function send(string $phone, string $message, ?string $code = null): void
    {
        $driver = (string) config('services.sms.driver', 'log');

        match ($driver) {
            'kavenegar' => $this->sendKavenegar($phone, $message),
            'smsir' => $this->sendSmsIr($phone, $message, $code),
            default => $this->sendLog($phone, $message),
        };
    }

    private function sendLog(string $phone, string $message): void
    {
        Log::info('SMS (log driver)', ['phone' => $phone, 'message' => $message]);
    }

    private function sendKavenegar(string $phone, string $message): void
    {
        // Placeholder for a real Kavenegar integration. Falls back to log if
        // credentials are missing so dev/test flows don't break.
        $apiKey = (string) config('services.sms.kavenegar.api_key');

        if ($apiKey === '') {
            $this->sendLog($phone, $message);

            return;
        }

        // Intentionally not implemented: integrate the real HTTP client here.
        $this->sendLog($phone, $message);
    }

    /**
     * sms.ir uses two main endpoints:
     *   POST {base}/v1/send/verify  — templated, parameters substituted into
     *                                  a registered template; ideal for OTPs.
     *   POST {base}/v1/send/bulk    — free-form text from a registered line.
     *
     * Both require the X-API-KEY header and a JSON body. We prefer the
     * verify endpoint when a numeric $code and a verify_template_id are
     * configured. If credentials are missing we fall back to log so dev
     * flows keep working.
     */
    private function sendSmsIr(string $phone, string $message, ?string $code): void
    {
        $apiKey = (string) config('services.sms.smsir.api_key');
        $baseUrl = rtrim((string) config('services.sms.smsir.base_url', 'https://api.sms.ir'), '/');

        if ($apiKey === '') {
            $this->sendLog($phone, $message);

            return;
        }

        $templateId = (int) config('services.sms.smsir.verify_template_id');
        $parameterName = (string) config('services.sms.smsir.parameter_name', 'Code');

        // OTP path: templated verify endpoint.
        if ($code !== null && $templateId > 0) {
            $payload = [
                'mobile' => $phone,
                'templateId' => $templateId,
                'parameters' => [
                    ['name' => $parameterName, 'value' => $code],
                ],
            ];

            $this->postJson("{$baseUrl}/v1/send/verify", $apiKey, $payload, $phone);

            return;
        }

        // Free-form path: bulk endpoint.
        $lineNumber = (string) config('services.sms.smsir.line_number');
        if ($lineNumber === '') {
            $this->sendLog($phone, $message);

            return;
        }

        $payload = [
            'lineNumber' => $lineNumber,
            'messageText' => $message,
            'mobiles' => [$phone],
        ];

        $this->postJson("{$baseUrl}/v1/send/bulk", $apiKey, $payload, $phone);
    }

    /**
     * Wraps the HTTP POST so logging stays consistent across endpoints.
     * Network failures are logged but never thrown — the OTP code is
     * already stored in the DB and a failure here shouldn't break the
     * verify endpoint that follows.
     */
    private function postJson(string $url, string $apiKey, array $payload, string $phone): void
    {
        try {
            $res = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, $payload);

            if (! $res->successful()) {
                Log::warning('SMS (sms.ir) non-2xx', [
                    'phone' => $phone,
                    'url' => $url,
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SMS (sms.ir) request failed', [
                'phone' => $phone,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
