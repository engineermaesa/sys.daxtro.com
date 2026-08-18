<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetsReader
{
    /**
     * Fetch rows from a sheet range as raw arrays (first row = header).
     */
    public function getRows(string $range): array
    {
        $spreadsheetId = config('services.google_sheets.spreadsheet_id');

        if (! $spreadsheetId) {
            throw new RuntimeException('GOOGLE_SHEETS_SATISFACTION_SURVEY_ID is not configured.');
        }

        $response = Http::withToken($this->getAccessToken())
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}")
            ->throw();

        return $response->json('values', []);
    }

    protected function getAccessToken(): string
    {
        return Cache::remember('google_sheets_access_token', now()->addMinutes(50), function () {
            $credentials = $this->loadServiceAccount();
            $jwt = $this->buildSignedJwt($credentials);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ])->throw();

            return $response->json('access_token');
        });
    }

    protected function loadServiceAccount(): array
    {
        $path = config('services.google_sheets.service_account_path');

        if (! is_file($path)) {
            throw new RuntimeException("Google service account file not found at: {$path}");
        }

        return json_decode(file_get_contents($path), true);
    }

    protected function buildSignedJwt(array $credentials): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        openssl_sign("{$header}.{$payload}", $signature, $credentials['private_key'], 'sha256WithRSAEncryption');

        return "{$header}.{$payload}." . $this->base64UrlEncode($signature);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
