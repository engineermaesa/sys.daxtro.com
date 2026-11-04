<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppSender
{
    public function send(string $phone, string $message): bool
    {
        $url = config('services.whatsapp.url');

        if (! $url) {
            Log::warning('WhatsApp provider not configured', ['to' => $phone, 'message' => $message]);

            return app()->isLocal();
        }

        // dev/testing: route every message to one number instead of the customer's
        $target = config('services.whatsapp.test_to') ?: $phone;

        try {
            // Fonnte: raw token in Authorization (no "Bearer"), form fields target + message
            $response = Http::withHeaders(['Authorization' => (string) config('services.whatsapp.token')])
                ->asForm()
                ->timeout(10)
                ->post($url, ['target' => $this->normalize($target), 'message' => $message]);

            return $response->successful() && $response->json('status') === true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;
    }
}
