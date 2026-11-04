<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IDR value of 1 USD / 1 CNY. Live rates come from Frankfurter (ECB daily data),
 * cached for an hour; on failure the last good value, then the .env fallback is used.
 */
class ExchangeRateService
{
    private const CACHE_KEY = 'exchange_rates.idr';
    private const LAST_GOOD_KEY = 'exchange_rates.idr.last_good';

    public function get(): array
    {
        if ($cached = Cache::get(self::CACHE_KEY)) {
            return $cached;
        }

        try {
            $res = Http::timeout(5)->get('https://api.frankfurter.dev/v1/latest', [
                'base' => 'USD',
                'symbols' => 'IDR,CNY',
            ]);

            $idr = (float) $res->json('rates.IDR');
            $cny = (float) $res->json('rates.CNY');

            if ($res->successful() && $idr > 0 && $cny > 0) {
                $rates = [
                    'idr_per_usd' => round($idr, 4),
                    'idr_per_cny' => round($idr / $cny, 4),
                    'as_of' => $res->json('date'),
                    'source' => 'frankfurter',
                ];
                Cache::put(self::CACHE_KEY, $rates, now()->addHour());
                Cache::forever(self::LAST_GOOD_KEY, $rates);

                return $rates;
            }
        } catch (\Throwable $e) {
            Log::warning('Exchange rate fetch failed: ' . $e->getMessage());
        }

        // not cached, so the next request retries the live API
        return Cache::get(self::LAST_GOOD_KEY) ?? [
            'idr_per_usd' => (float) config('services.exchange_rates.fallback_usd'),
            'idr_per_cny' => (float) config('services.exchange_rates.fallback_cny'),
            'as_of' => null,
            'source' => 'fallback',
        ];
    }
}
