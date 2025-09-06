<?php

namespace App\Services\Currency;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyConverter
{
    protected string $apiKey;
    protected string $base;

    public function __construct(?string $apiKey = null, string $base = 'USD')
    {
        // prefer ENV but allow direct injection
        $this->apiKey = $apiKey ?: (string) config('services.currencyapi.key');
        $this->base   = $base;
    }

    /**
     * Get FX rate for $code relative to BASE (default USD).
     * Example: rate('INR') -> 83.20 (INR per 1 USD)
     */
    public function rate(string $code): float
    {
        $code = strtoupper(trim($code ?: 'USD'));
        if ($code === $this->base) return 1.0;

        return Cache::remember("fx:{$this->base}:{$code}", now()->addMinutes(30), function () use ($code) {
            // Using your provided endpoint; add base_currency and currencies for efficiency
            $url = 'https://api.currencyapi.com/v3/latest';
            $resp = Http::timeout(10)->get($url, [
                'apikey'         => $this->apiKey,
                'base_currency'  => $this->base,
                'currencies'     => $code,
            ]);

            if (!$resp->ok()) {
                // fallback: assume 1 if API fails (prevents user being stuck)
                return 1.0;
            }

            $json = $resp->json();
            // expected structure: ['data' => ['INR' => ['code'=>'INR','value'=>83.xx]]]
            $val = $json['data'][$code]['value'] ?? null;
            if (!$val || !is_numeric($val)) return 1.0;

            return (float) $val;
        });
    }

    /**
     * Convert an amount between two ISO codes, both relative to the same base.
     * We first normalize to USD (base), then to target.
     */
    public function convert(float $amount, string $fromCode, string $toCode): float
    {
        $from = strtoupper($fromCode ?: $this->base);
        $to   = strtoupper($toCode ?: $this->base);

        if ($from === $to) return round($amount, 2);

        // rates are "units of currency per 1 BASE"
        // amount_in_base = amount / rate(from)
        // amount_in_target = amount_in_base * rate(to)
        $rateFrom = $this->rate($from); // e.g., INR per 1 USD
        $rateTo   = $this->rate($to);   // e.g., EUR per 1 USD

        // protect divide-by-zero
        if ($rateFrom <= 0) $rateFrom = 1.0;

        $amountInBase   = $amount / $rateFrom;
        $amountInTarget = $amountInBase * $rateTo;

        return round($amountInTarget, 2);
    }
}
