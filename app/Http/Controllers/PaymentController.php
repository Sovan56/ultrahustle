<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use App\Models\Product;
use App\Models\ProductPricing;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Str;
use App\Support\Currency;
// TOP of file:
use App\Models\MyOrder;
use App\Models\PlatformSetting;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\Currency\CurrencyConverter;

class PaymentController extends Controller
{

    public function walletQuote(Request $r)
    {
        $r->validate([
            'product_id' => 'required|integer|exists:products,id',
            'tier'       => 'required|string|in:basic,standard,premium',
        ]);

        $product = Product::with(['type', 'country', 'user'])->findOrFail($r->product_id);
        $pricing = ProductPricing::with('country')
            ->where('product_id', $product->id)
            ->where('tier', $r->tier)->firstOrFail();

        $viewer      = Auth::user() ?? User::find(session('user_id'));
        $targetCode  = Currency::codeForUser($viewer);
        $symbol      = Currency::symbol($targetCode);

        $fromCode = $pricing->country?->currency ?? $product->country?->currency ?? 'USD';

        $fx   = new CurrencyConverter();              // ← keep your class
        $base = (float) $pricing->price;
        if ($fromCode !== $targetCode) {
            $base = (float) $fx->convert($base, $fromCode, $targetCode);
        }

        $feeP = (float) (PlatformSetting::get('platform_fee_percent', 5) ?? 5);
        $gstP = (float) (PlatformSetting::get('gst_percent', 18) ?? 18);
        $feeA = round($base * $feeP / 100, 2);
        $gstA = round(($base + $feeA) * $gstP / 100, 2);
        $total = round($base + $feeA + $gstA, 2);

        // Currency guard hint
        $profileCurrency = $viewer?->currency;
        $canPay = true;
        $blockMsg = null;
        if ($viewer) {
            if (!$viewer->country_id || !$profileCurrency || $profileCurrency !== $targetCode) {
                $canPay = false;
                $blockMsg = 'Please set your country first in Profile.';
            }
        }

        return response()->json([
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
            'seller_currency'       => $product->user?->country_id ? (Country::find($product->user->country_id)?->currency ?? 'USD') : 'USD',
        ]);
    }

    /**
     * Deducts from wallet and fulfills Digital/Course orders.
     * Assumes you keep user's wallet balance on users table as `wallet_balance` (decimal).
     * If your field name differs, adjust the two marked lines.
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

        // Currency: buyer pays in their profile country currency
        $buyerCountry = $buyer->country_id ? Country::find($buyer->country_id) : null;
        $buyerCode = Currency::codeForUser($buyer);
        $symbol    = Currency::symbol($buyerCode);

        // Guard: profile must be set and consistent
        abort_if(!$buyer->country_id || !$buyer->currency || $buyer->currency !== $buyerCode, 422, 'Please set your country first in Profile.');

        // Price base from pricing/product currency -> buyer currency
        $fromCode = $pricing->country?->currency ?? $product->country?->currency ?? 'USD';
        $fx = new CurrencyConverter();
        $base = (float)$pricing->price;
        if ($fromCode !== $buyerCode) {
            $base = (float)$fx->convert($base, $fromCode, $buyerCode);
        }

        $feeP = (float) (PlatformSetting::get('platform_fee_percent', 5) ?? 5);
        $gstP = (float) (PlatformSetting::get('gst_percent', 18) ?? 18);
        $feeA = round($base * $feeP / 100, 2);
        $gstA = round(($base + $feeA) * $gstP / 100, 2);
        $total = round($base + $feeA + $gstA, 2);

        // Seller payout settings
        $sellerFeeP = (float) (PlatformSetting::get('seller_platform_fee_percent', 20) ?? 20); // percent
        $seller = $product->user()->first(); // seller user
        $sellerCountry = $seller?->country_id ? Country::find($seller->country_id) : null;
        $sellerCode = $sellerCountry?->currency ?? ($seller->currency ?: 'USD');

        // Block duplicate purchase
        $already = MyOrder::where('buyer_id', $buyer->id)->where('product_id', $product->id)->where('status', 'paid')->exists();
        abort_if($already, 422, 'You already purchased this product.');

        $order = \DB::transaction(function () use (
            $buyer,
            $seller,
            $product,
            $pricing,
            $buyerCode,
            $sellerCode,
            $fx,
            $base,
            $feeP,
            $feeA,
            $gstP,
            $gstA,
            $total,
            $sellerFeeP
        ) {
            $buyer->refresh();
            $balance = (float)($buyer->wallet ?? 0.0);
            abort_if($balance < $total, 422, 'Insufficient wallet balance');

            // 1) DEBIT buyer
            $buyer->wallet = $balance - $total;
            $buyer->save();

            $walletTxnId = null;
            if (class_exists(\App\Models\WalletTransaction::class)) {
                $txn = \App\Models\WalletTransaction::create([
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

            // 2) CREDIT seller
            //    Apply seller platform fee on BASE in BUYER currency, THEN convert remainder to SELLER currency.
            $sellerFeeAmountBuyerCcy = round($base * $sellerFeeP / 100, 2);
            $netForSellerBuyerCcy    = max(0, round($base - $sellerFeeAmountBuyerCcy, 2));

            $fxRate = 1.0;
            $creditAmountSellerCcy = $netForSellerBuyerCcy;
            if ($sellerCode !== $buyerCode) {
                $converted = (float)$fx->convert($netForSellerBuyerCcy, $buyerCode, $sellerCode);
                $fxRate    = $netForSellerBuyerCcy > 0 ? ($converted / $netForSellerBuyerCcy) : 1.0;
                $creditAmountSellerCcy = round($converted, 2);
            }

            if ($seller) {
                $seller->refresh();
                $seller->wallet = (float)($seller->wallet ?? 0) + $creditAmountSellerCcy;
                $seller->save();

                if (class_exists(\App\Models\WalletTransaction::class)) {
                    \App\Models\WalletTransaction::create([
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

            // 3) Create MyOrder & fulfill
            $mo = \App\Models\MyOrder::create([
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

            $this->fulfillInstant($product, $buyer, $mo);

            return $mo;
        });

        // After success, send them to success page (you can add a "Write a review" link there),
        // OR redirect back to the product page with a review flag:
        return response()->json([
            'ok'       => true,
            'redirect' => route('orders.success', $order->id),
            // 'redirect' => route('product.details', $r->product_id) . '?review=1#reviews',
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

    /**
     * Fulfill for wallet checkout path (Digital/Course)
     * - Digital: make files downloadable via signed URLs (3 days)
     * - Course: email URLs
     */
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
