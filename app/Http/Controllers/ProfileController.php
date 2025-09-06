<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAdminAnotherDetail;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    Auth,
    Storage,
    Validator,
    DB,
    Cache,
    Http
};

class ProfileController extends Controller
{
    /**
     * Base currency for FX normalization.
     * We treat USD as the base (as you requested: default show in dollar).
     */
    protected string $fxBase = 'USD';

    /**
     * Your CurrencyAPI key (env or hardcoded fallback).
     * You gave: cur_live_okDJETF94IYrL5fKIP1GY7f2VlcgPAOrPvaT5Gfo
     */
    protected string $currencyApiKey;

    public function __construct()
    {
        // Prefer .env if you add CURRENCYAPI_KEY there; else use provided key.
        $this->currencyApiKey = config('services.currencyapi.key', 'cur_live_okDJETF94IYrL5fKIP1GY7f2VlcgPAOrPvaT5Gfo');
    }

  public function show(\Illuminate\Http\Request $request)
{
    $authUser = \Auth::user() ?? \App\Models\User::find(session('user_id'));
    abort_if(!$authUser, 403);

    // Eager load relations
    $authUser->load(['anotherDetail', 'country', 'kycSubmission']);

    // Normalize socials to array (works whether cast exists or not)
    $rawSocials = $authUser->anotherDetail->social_media_link ?? [];
    if (!is_array($rawSocials)) {
        $decoded = json_decode($rawSocials, true);
        $rawSocials = is_array($decoded) ? $decoded : [];
    }

    // Convenience flag for Blade (avoid case issues)
    $kyc = $authUser->kycSubmission;
    $kycApproved = $kyc && \Illuminate\Support\Str::lower((string)$kyc->status) === 'approved';

    return view('UserAdmin.profile', [
        'user'         => $authUser,
        'detail'       => $authUser->anotherDetail,
        'socials'      => $rawSocials,
        'country'      => $authUser->country,
        'kyc'          => $kyc,
        'kycApproved'  => $kycApproved, // <— use this in Blade to disable form / show badge
    ]);
}

    public function update(Request $request)
    {
        $authUser = Auth::user() ?? User::find(session('user_id'));
        abort_if(!$authUser, 403);

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email,' . $authUser->id],
            'phone'      => ['required', 'string', 'max:25'],
            'bio'        => ['required', 'string'],
            'location'   => ['required', 'string', 'max:100'],
            'avatar'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'country_id' => ['nullable', 'exists:countries,id'],

            'socials'            => ['array'],
            'socials.*.platform' => ['nullable', 'string', 'max:50'],
            'socials.*.url'      => ['nullable', 'url', 'max:255'],
        ];

        $validated = Validator::make($request->all(), $rules)->validate();

        // --- Track old country/currency for FX conversion ---
        $oldCountry = $authUser->country_id
            ? Country::find($authUser->country_id)
            : null;

        $oldCurrencyCode = strtoupper($oldCountry->currency ?? $authUser->currency ?? $this->fxBase);
        if (!$oldCurrencyCode) $oldCurrencyCode = $this->fxBase; // safety

        // --- Assign basic fields ---
        $authUser->first_name   = $validated['first_name'];
        $authUser->last_name    = $validated['last_name'];
        $authUser->email        = $validated['email'];
        $authUser->phone_number = $validated['phone'];

        // --- Country & Currency ---
        $newCountryId = $validated['country_id'] ?? null;
        $authUser->country_id = $newCountryId;

        $newCountry = $newCountryId ? Country::find($newCountryId) : null;
        $newCurrencyCode = strtoupper($newCountry->currency ?? $this->fxBase);

        // Mirror the currency code on users table if you keep one there
        $authUser->currency = $newCountry ? $newCountry->currency : null;

        // --- FX Conversion: only if currency actually changes & user has a wallet column ---
        // Default wallet is considered in OLD currency (USD if none). Convert to NEW currency.
        if (
            property_exists($authUser, 'wallet') || array_key_exists('wallet', $authUser->getAttributes())
        ) {
            if ($newCountry && $oldCurrencyCode !== $newCurrencyCode) {
                $authUser->wallet = $this->convertAmount(
                    (float) ($authUser->wallet ?? 0.00),
                    $fromCode = $oldCurrencyCode,
                    $toCode   = $newCurrencyCode
                );
            } elseif (!$oldCountry && $newCountry && $oldCurrencyCode !== $newCurrencyCode) {
                // No previous country (treated as USD) -> new country => convert USD -> new currency
                $authUser->wallet = $this->convertAmount(
                    (float) ($authUser->wallet ?? 0.00),
                    $fromCode = $this->fxBase,
                    $toCode   = $newCurrencyCode
                );
            }
        }

        $authUser->save();

        // Ensure detail exists (keyed by users.unique_id)
        $detail = $authUser->anotherDetail ?: new UserAdminAnotherDetail([
            'user_admin_id' => $authUser->unique_id,
        ]);

        // Avatar upload (optional)
        if ($request->hasFile('avatar')) {
            $ext  = strtolower($request->file('avatar')->getClientOriginalExtension());
            $dir  = "profiles/{$authUser->unique_id}";
            $path = "$dir/avatar.$ext";

            if ($detail->profile_picture && Storage::disk('public')->exists($detail->profile_picture)) {
                Storage::disk('public')->delete($detail->profile_picture);
            }
            Storage::disk('public')->putFileAs($dir, $request->file('avatar'), "avatar.$ext");
            $detail->profile_picture = $path;
        }

        // Socials JSON (only keep rows with a URL)
        $socialsInput = collect($request->input('socials', []))
            ->filter(fn($row) => !empty($row['url']))
            ->mapWithKeys(function ($row) {
                $platform = strtolower(trim($row['platform'] ?? 'website'));
                return [$platform => trim($row['url'])];
            })
            ->all();

        // OPTIONAL: sanitize bio to allow only safe tags
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><a><h1><h2><h3><blockquote>';
        $cleanBio    = strip_tags($validated['bio'], $allowedTags);

        $detail->location            = $validated['location'];
        $detail->social_media_link   = $socialsInput;
        $detail->profile_description = $cleanBio; // stored as HTML; rendered as HTML in Blade
        $detail->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    /* ==========================
     |  Helpers: FX conversion  |
     ===========================*/

    /**
     * Convert an amount from $fromCode to $toCode using CurrencyAPI (base USD).
     * We compute:
     *   amount_in_base = amount / rate(from)
     *   amount_in_to   = amount_in_base * rate(to)
     */
    protected function convertAmount(float $amount, string $fromCode, string $toCode): float
    {
        $from = strtoupper($fromCode ?: $this->fxBase);
        $to   = strtoupper($toCode ?: $this->fxBase);

        if ($from === $to) {
            return round($amount, 2);
        }

        $rateFrom = $this->fxRate($from); // units of FROM per 1 USD
        $rateTo   = $this->fxRate($to);   // units of TO per 1 USD

        if ($rateFrom <= 0) $rateFrom = 1.0;

        $amountInBase   = $amount / $rateFrom;   // normalize to USD
        $amountInTarget = $amountInBase * $rateTo;

        return round($amountInTarget, 2);
        // Note: rounding to 2 decimals because your wallet keeps two digits.
    }

    /**
     * Get FX rate for $code relative to base (USD).
     * Caches for 30 minutes to reduce API calls.
     * If API fails, returns 1.0 to avoid blocking updates.
     */
    protected function fxRate(string $code): float
    {
        $code = strtoupper(trim($code ?: $this->fxBase));
        if ($code === $this->fxBase) {
            return 1.0;
        }

        $cacheKey = "fx:{$this->fxBase}:{$code}";
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($code) {
            try {
                $resp = Http::timeout(10)->get('https://api.currencyapi.com/v3/latest', [
                    'apikey'        => $this->currencyApiKey,
                    'base_currency' => $this->fxBase,
                    'currencies'    => $code,
                ]);

                if (!$resp->ok()) {
                    return 1.0;
                }

                $json = $resp->json();
                // Expected: ['data' => ['INR' => ['code'=>'INR','value'=>83.xx]]]
                $val = $json['data'][$code]['value'] ?? null;
                if (!is_numeric($val)) {
                    return 1.0;
                }

                return (float) $val;
            } catch (\Throwable $e) {
                // Log if you want: \Log::warning('FX rate fetch failed', ['err'=>$e->getMessage()]);
                return 1.0;
            }
        });
    }

    public function updateCountryAjax(Request $request)
{
    $user = Auth::user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $data = $request->validate([
        'country_id' => ['required','integer','exists:countries,id'],
    ]);

    // fetch old/new country
    $oldCountry = $user->country_id ? Country::find($user->country_id) : null;
    $newCountry = Country::find($data['country_id']);

    // currency codes & symbol (fallbacks: USD/$)
    $baseUSD      = 'USD';
    $oldCode      = strtoupper($oldCountry->currency ?? $user->currency ?? $baseUSD);
    $newCode      = strtoupper($newCountry->currency ?? $baseUSD);
    $newSymbol    = $newCountry->currency_symbol ?? '$';

    // inline FX rate helper (relative to USD)
    $getRate = function (string $code) use ($baseUSD) : float {
        $code = strtoupper($code ?: $baseUSD);
        if ($code === $baseUSD) return 1.0;

        return Cache::remember("fx:{$baseUSD}:{$code}", now()->addMinutes(30), function () use ($code, $baseUSD) {
            try {
                $resp = Http::timeout(10)->get('https://api.currencyapi.com/v3/latest', [
                    'apikey'        => config('services.currencyapi.key', 'cur_live_okDJETF94IYrL5fKIP1GY7f2VlcgPAOrPvaT5Gfo'),
                    'base_currency' => $baseUSD,
                    'currencies'    => $code,
                ]);
                if (!$resp->ok()) return 1.0;
                $json = $resp->json();
                $val  = $json['data'][$code]['value'] ?? null; // e.g. 83.2 INR per 1 USD
                return is_numeric($val) ? (float)$val : 1.0;
            } catch (\Throwable $e) {
                // \Log::warning('FX fetch failed', ['err'=>$e->getMessage()]);
                return 1.0;
            }
        });
    };

    // convert wallet if currency changed
    if (($user->wallet ?? null) !== null && $oldCode !== $newCode) {
        $rateFrom = $getRate($oldCode); // units of OLD per 1 USD
        $rateTo   = $getRate($newCode); // units of NEW per 1 USD
        if ($rateFrom <= 0) $rateFrom = 1.0;
        $amountUSD   = ((float)$user->wallet) / $rateFrom;   // normalize to USD
        $amountNew   = $amountUSD * $rateTo;                 // to target
        $user->wallet = round($amountNew, 2);                // keep two decimals
    }

    // persist country & currency code
    $user->country_id = (int) $data['country_id'];
    $user->currency   = $newCountry->currency ?? null; // if you store it on users
    $user->save();

    return response()->json([
        'success'         => true,
        'currency_symbol' => $newSymbol,
        'currency'        => $newCode,
        'wallet'          => number_format((float) $user->wallet, 2),
        'message'         => 'Country & currency updated.',
    ]);
}

}
