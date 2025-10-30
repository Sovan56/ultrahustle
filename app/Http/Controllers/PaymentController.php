<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

use App\Models\Product;
use App\Models\ProductPricing;
use App\Models\Country;
use App\Models\User;
use App\Models\MyOrder;
use App\Models\PlatformSetting;
use App\Models\WalletTransaction;

use App\Services\Currency\CurrencyConverter;
use App\Support\Currency;

class PaymentController extends Controller
{
    /**
     * Return a wallet quote (no auth required; we’ll still tailor to viewer if logged in).
     * Always returns JSON 200 with { ok:true/false } so frontend never “fails to parse”.
     */
    public function walletQuote(Request $r)
    {
        try {
            $r->validate([
                'product_id' => 'required|integer|exists:products,id',
                'tier'       => 'required|string|in:basic,standard,premium',
            ]);

            $product = Product::with(['type', 'country', 'user'])->findOrFail($r->product_id);

            $pricing = ProductPricing::with('country')
                ->where('product_id', $product->id)
                ->where('tier', $r->tier)
                ->first();

            if (!$pricing) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'Tier not available for this product.',
                ], 200);
            }

            $viewer     = Auth::user() ?? User::find(session('user_id'));
            $targetCode = Currency::codeForUser($viewer) ?: ($product->country->currency ?? 'USD');
            $symbol     = Currency::symbol($targetCode) ?: '$';

            $fromCode = $pricing->country?->currency
                     ?? $product->country?->currency
                     ?? 'USD';

            $fx   = new CurrencyConverter();
            $base = (float) $pricing->price;
            if ($fromCode !== $targetCode) {
                // guard against any upstream conversion exceptions
                try {
                    $base = (float) $fx->convert($base, $fromCode, $targetCode);
                } catch (\Throwable $e) {
                    // fall back to raw base if FX fails (still return a quote)
                }
            }

            $feeP  = (float) (PlatformSetting::get('platform_fee_percent', 5) ?? 5);
            $gstP  = (float) (PlatformSetting::get('gst_percent', 18) ?? 18);
            $feeA  = round($base * $feeP / 100, 2);
            $gstA  = round(($base + $feeA) * $gstP / 100, 2);
            $total = round($base + $feeA + $gstA, 2);

            // Guard hint (don’t 4xx — just tell the UI)
            $canPay   = true;
            $blockMsg = null;

            if ($viewer) {
                $profileCurrency = $viewer->currency ?: null;
                if (!$viewer->country_id || !$profileCurrency || $profileCurrency !== $targetCode) {
                    $canPay   = false;
                    $blockMsg = 'Please set your country first in Profile.';
                }
            }

            return response()->json([
                'ok'                    => true,
                'base'                  => $base,
                'platform_fee_percent'  => $feeP,
                'platform_fee_amount'   => $feeA,
                'gst_percent'           => $gstP,
                'gst_amount'            => $gstA,
                'total'                 => $total,
                'currency'              => $targetCode,
                'currency_symbol'       => $symbol,
                'tier'                  => $r->tier,
                'product_name'          => $product->name,
                'can_pay'               => $canPay,
                'block_reason'          => $blockMsg,
                'seller_currency'       => $product->user?->country_id
                                            ? (Country::find($product->user->country_id)?->currency ?? 'USD')
                                            : 'USD',
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'ok'    => false,
                'error' => $ve->getMessage(),
                'errors'=> $ve->errors(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => 'Failed to prepare quote.',
            ], 200);
        }
    }

    /**
     * Wallet checkout (requires auth).
     */
    public function walletCheckout(Request $r)
    {
        $r->validate([
            'product_id' => 'required|integer|exists:products,id',
            'tier'       => 'required|string|in:basic,standard,premium',
        ]);

        $buyer = Auth::user() ?? User::find(session('user_id'));
        abort_if(!$buyer, 403);

        $product = Product::with(['type', 'user', 'country'])->findOrFail($r->product_id);
        $pricing = ProductPricing::with('country')
            ->where('product_id', $product->id)
            ->where('tier', $r->tier)->firstOrFail();

        // Buyer currency
        $buyerCode = Currency::codeForUser($buyer) ?: 'USD';
        $symbol    = Currency::symbol($buyerCode) ?: '$';

        // Guard: profile must be set and consistent
        abort_if(!$buyer->country_id || !$buyer->currency || $buyer->currency !== $buyerCode, 422, 'Please set your country first in Profile.');

        // Convert base into buyer currency
        $fromCode = $pricing->country?->currency ?? $product->country?->currency ?? 'USD';
        $fx = new CurrencyConverter();
        $base = (float)$pricing->price;
        if ($fromCode !== $buyerCode) {
            try {
                $base = (float)$fx->convert($base, $fromCode, $buyerCode);
            } catch (\Throwable $e) {
                // keep $base as-is if conversion fails
            }
        }

        $feeP = (float) (PlatformSetting::get('platform_fee_percent', 5) ?? 5);
        $gstP = (float) (PlatformSetting::get('gst_percent', 18) ?? 18);
        $feeA = round($base * $feeP / 100, 2);
        $gstA = round(($base + $feeA) * $gstP / 100, 2);
        $total = round($base + $feeA + $gstA, 2);

        // Seller payout settings
        $sellerFeeP = (float) (PlatformSetting::get('seller_platform_fee_percent', 20) ?? 20);
        $seller = $product->user()->first();
        $sellerCode = $seller?->country_id ? (Country::find($seller->country_id)?->currency ?? 'USD') : ($seller->currency ?: 'USD');

        // Block duplicate purchase
        $already = MyOrder::where('buyer_id', $buyer->id)
            ->where('product_id', $product->id)
            ->where('status', 'paid')
            ->exists();
        abort_if($already, 422, 'You already purchased this product.');

        $order = DB::transaction(function () use (
            $buyer, $seller, $product, $pricing,
            $buyerCode, $sellerCode, $fx,
            $base, $feeP, $feeA, $gstP, $gstA, $total, $sellerFeeP
        ) {
            $buyer->refresh();
            $balance = (float)($buyer->wallet ?? 0.0);
            abort_if($balance < $total, 422, 'Insufficient wallet balance');

            // 1) DEBIT buyer
            $buyer->wallet = $balance - $total;
            $buyer->save();

            $walletTxnId = null;
            if (class_exists(WalletTransaction::class)) {
                $txn = WalletTransaction::create([
                    'user_id'  => $buyer->id,
                    'type'     => 'debit',
                    'amount'   => $total,
                    'currency' => $buyerCode,
                    'reason'   => 'Wallet checkout for product #' . $product->id,
                    'meta'     => [
                        'product_id'  => $product->id,
                        'pricing_id'  => $pricing->id,
                        'tier'        => $pricing->tier,
                        'base'        => $base,
                        'platform_fee_percent' => $feeP,
                        'platform_fee_amount'  => $feeA,
                        'gst_percent' => $gstP,
                        'gst_amount'  => $gstA,
                        'total'       => $total,
                        'paid_in'     => $buyerCode,
                    ],
                ]);
                $walletTxnId = (string)$txn->id;
            }

            // 2) CREDIT seller (apply seller fee on base in buyer ccy, then convert)
            $sellerFeeAmountBuyerCcy = round($base * $sellerFeeP / 100, 2);
            $netForSellerBuyerCcy    = max(0, round($base - $sellerFeeAmountBuyerCcy, 2));

            $fxRate = 1.0;
            $creditAmountSellerCcy = $netForSellerBuyerCcy;
            if ($sellerCode !== $buyerCode) {
                try {
                    $converted = (float)$fx->convert($netForSellerBuyerCcy, $buyerCode, $sellerCode);
                    $fxRate    = $netForSellerBuyerCcy > 0 ? ($converted / $netForSellerBuyerCcy) : 1.0;
                    $creditAmountSellerCcy = round($converted, 2);
                } catch (\Throwable $e) {
                    // keep buyer ccy if conversion fails (rare)
                }
            }

            if ($seller) {
                $seller->refresh();
                $seller->wallet = (float)($seller->wallet ?? 0) + $creditAmountSellerCcy;
                $seller->save();

                if (class_exists(WalletTransaction::class)) {
                    WalletTransaction::create([
                        'user_id'  => $seller->id,
                        'type'     => 'credit',
                        'amount'   => $creditAmountSellerCcy,
                        'currency' => $sellerCode,
                        'reason'   => 'Sale of product #' . $product->id,
                        'meta'     => [
                            'product_id'   => $product->id,
                            'pricing_id'   => $pricing->id,
                            'tier'         => $pricing->tier,
                            'buyer_currency'  => $buyerCode,
                            'seller_currency' => $sellerCode,
                            'base_in_buyer'   => $base,
                            'seller_fee_percent' => $sellerFeeP,
                            'seller_fee_in_buyer' => $sellerFeeAmountBuyerCcy,
                            'net_in_buyer'      => $netForSellerBuyerCcy,
                            'fx_rate'           => $fxRate,
                            'credited_in_seller' => $creditAmountSellerCcy,
                        ],
                    ]);
                }
            }

            // 3) Create order & fulfill
            $mo = MyOrder::create([
                'buyer_id'             => $buyer->id,
                'product_id'           => $product->id,
                'product_type_id'      => $product->product_type_id ?? $product->type_id ?? null,
                'tier'                 => $pricing->tier,
                'base_amount'          => $base,
                'platform_fee_percent' => $feeP,
                'platform_fee_amount'  => $feeA,
                'gst_percent'          => $gstP,
                'gst_amount'           => $gstA,
                'total_amount'         => $total,
                'currency'             => $buyerCode,
                'wallet_txn_id'        => $walletTxnId,
                'paid_at'              => now(),
                'status'               => 'paid',
                'meta'                 => ['pricing_id' => $pricing->id],
            ]);

            // instant fulfil hook (if you had it)
            if (method_exists($this, 'fulfillInstant')) {
                try { $this->fulfillInstant($product, $buyer, $mo); } catch (\Throwable $e) {}
            }

            return $mo;
        });

        return response()->json([
            'ok'       => true,
            'redirect' => route('orders.success', $order->id),
        ]);
    }



    /**
     * Success page
     */
    public function success(int $order)
    {
        $buyer = Auth::user() ?? \App\Models\User::find(session('user_id'));
        abort_if(!$buyer, 403);

        $o = MyOrder::with('product')->findOrFail($order);
        abort_if($o->buyer_id !== $buyer->id, 403);

        return view('orders.success', ['order' => $o]);
    }

    /**
     * User orders JSON for My Orders page
     */
    public function myOrdersData(Request $r)
    {
        $buyer = Auth::user() ?? \App\Models\User::find(session('user_id'));
        abort_if(!$buyer, 403);

        $q = MyOrder::with('product')
            ->where('buyer_id', $buyer->id);

        if ($type = $r->get('type')) { // digital|course|service
            $q->whereHas('product.type', function ($qq) use ($type) {
                $qq->where('name', 'like', "%$type%");
            });
        }
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }

        $rows = $q->orderByDesc('id')->paginate(15);
        return response()->json($rows);
    }

   
    protected function fulfillInstant(Product $product, User $buyer, MyOrder $mo): void
    {
        $typeName = Str::lower($product->type->name ?? '');

        if (Str::contains($typeName, 'digital')) {
            $files = collect($product->files ?? [])->filter()->map(function ($path) {
                return $this->mediaUrlFor($path); // <— Always resolve via /media
            })->values()->all();

            $mo->update([
                'delivery_files' => $files,
                'status'         => 'delivered',
            ]);

            try {
                \Mail::send('emails.digital_delivery', ['product' => $product, 'buyer' => $buyer, 'order' => $mo, 'files' => $files], function ($m) use ($buyer, $product) {
                    $m->to($buyer->email, $buyer->name ?? 'Buyer')
                        ->subject('Your digital files • ' . $product->name);
                });
            } catch (\Throwable $e) { /* ignore mail errors */
            }
        } elseif (Str::contains($typeName, 'course')) {
            $urls = array_values(array_filter($product->urls ?? []));
            $mo->update([
                'course_urls' => $urls,
                'status'      => 'delivered',
            ]);

            try {
                \Mail::send('emails.course_access', ['product' => $product, 'buyer' => $buyer, 'order' => $mo, 'urls' => $urls], function ($m) use ($buyer, $product) {
                    $m->to($buyer->email, $buyer->name ?? 'Buyer')
                        ->subject('Your course access links • ' . $product->name);
                });
            } catch (\Throwable $e) { /* ignore mail errors */
            }
        }
    }

    protected function currencySymbol(string $code): string
    {
        $byCountry = Country::where('currency', $code)->first();
        if ($byCountry && $byCountry->currency_symbol) return $byCountry->currency_symbol;
        $map = [
            'USD' => '$',
            'INR' => '₹',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'SGD' => 'S$',
            'AED' => 'د.إ'
        ];
        return $map[$code] ?? $code;
    }


    protected function mediaUrlFor(string $path): string
    {
        // Already absolute?
        if (Str::startsWith($path, ['http://', 'https://'])) return $path;

        // Normalize: strip leading slashes + "storage/" + "public/"
        $clean = ltrim($path, '/');
        if (Str::startsWith($clean, 'storage/')) $clean = substr($clean, 8);
        if (Str::startsWith($clean, 'public/'))  $clean = substr($clean, 7);

        return route('media.pass', ['path' => $clean]);
    }
}
