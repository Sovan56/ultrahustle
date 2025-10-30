<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\ServiceMilestone;
use App\Models\ServiceOrder;
use App\Services\Currency\CurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServicePaymentController extends Controller
{
    public function approve(Request $request, \App\Models\ServiceOrder $order)
{
    $me = \Illuminate\Support\Facades\Auth::id();
    abort_unless($me && $order->buyer_id === $me, 403);

    // Must be in a state that can be approved
    if (!in_array($order->status, ['sent','reupdated','draft'], true)) {
        return back()->withErrors(['order' => 'This contract cannot be approved now.']);
    }

    // Accept confirmation via hidden input or header
    $confirmed = $request->boolean('confirm') || (string)$request->header('X-Pay-Confirm') === '1';
    if (!$confirmed) {
        return back()->withErrors(['payment' => 'Payment confirmation is required.']);
    }

    // Seller/order currency
    $sellerCode   = $order->currency_code   ?: 'USD';
    $sellerSymbol = $order->currency_symbol ?: '$';

    // Buyer currency (from relations)
    $buyer      = $order->buyer()->select('id','country_id')->with('country:id,currency,currency_symbol')->first();
    $buyerCode  = strtoupper($buyer->country->currency ?? 'USD');
    $buyerSym   = $buyer->country->currency_symbol ?? '$';

    // Buyer-side fee settings
    $buyerPct = (float) (\DB::table('platform_settings')->where('key', 'buyer_platform_fee_percent')->value('value')
                ?? \DB::table('platform_settings')->where('key', 'platform_fee_percent')->value('value') ?? 0);
    $gstPct   = (float) (\DB::table('platform_settings')->where('key', 'gst_percent')->value('value') ?? 0);
    if ($buyerPct < 0) $buyerPct = 0;
    if ($gstPct   < 0) $gstPct   = 0;

    // Compute buyer quote on the **seller subtotal** only
    $fx            = new \App\Services\Currency\CurrencyConverter();
    $buyerSubtotal = round($fx->convert((float)$order->subtotal, $sellerCode, $buyerCode), 2);
    $buyerFee      = round($buyerSubtotal * ($buyerPct / 100), 2);
    $buyerGST      = round(($buyerSubtotal + $buyerFee) * ($gstPct / 100), 2);
    $buyerTotal    = round($buyerSubtotal + $buyerFee + $buyerGST, 2);

    // Wallet deduction + HOLD == SUBTOTAL (in seller currency)
    $buyerModel = $order->buyer()->lockForUpdate()->first();

    return \DB::transaction(function () use ($order, $buyerModel, $buyerSubtotal, $buyerFee, $buyerGST, $buyerTotal, $buyerPct, $gstPct, $buyerCode, $buyerSym, $sellerCode, $sellerSymbol) {

        if ((float)$buyerModel->wallet < $buyerTotal) {
            return back()->withErrors(['wallet' => 'Insufficient wallet balance to approve this contract.']);
        }

        // Deduct buyer wallet (in buyer currency)
        $buyerModel->wallet = round(((float)$buyerModel->wallet - $buyerTotal), 2);
        $buyerModel->save();

        // ✅ HOLD is ONLY the seller subtotal; seller fees are taken per release
        $order->forceFill([
            'hold_amount' => round((float)$order->subtotal, 2),
            'status'      => 'approved_paid',
            // leave released_amount as-is (normally 0 for a new approval)
        ])->save();

        // Store buyer quote snapshot (auditable)
        $meta = (array) $order->meta;
        $meta['buyer_quote'] = [
            'currency_code'        => $buyerCode,
            'currency_symbol'      => $buyerSym,
            'subtotal'             => $buyerSubtotal,
            'platform_fee_percent' => $buyerPct,
            'platform_fee_amount'  => $buyerFee,
            'gst_percent'          => $gstPct,
            'gst_amount'           => $buyerGST,
            'total'                => $buyerTotal,
            'paid_at'              => now()->toIso8601String(),
        ];
        $order->meta = $meta;
        $order->save();

        try {
            \Mail::to($order->seller->email)->send(new \App\Mail\Service\ContractApprovedPaidMail($order));
        } catch (\Throwable $e) {}

        return redirect()
            ->route('service.contracts.show', $order->id)
            ->with('success', 'Approved and paid. Funds are on hold until milestones are released.');
    });
}


    /**
     * Buyer releases a single milestone to the seller.
     * - Seller platform fee is split evenly across milestones: total_seller_pct / milestones_count
     * - Credit seller in SELLER currency: milestone_price - seller_fee_part
     * - Update hold_amount (gross price) and released_amount (net payout)
     */
    public function release(Request $request, \App\Models\ServiceMilestone $milestone)
{
    $me = \Illuminate\Support\Facades\Auth::id();
    abort_unless($me && $milestone->order && $milestone->order->buyer_id === $me, 403);

    $order = $milestone->order;

    if (!in_array($order->status, ['approved_paid','in_progress'], true)) {
        return back()->withErrors(['order' => 'Order is not payable at the current status.']);
    }
    if ($milestone->status !== 'submitted') {
        return back()->withErrors(['milestone' => 'This milestone is not ready for release.']);
    }

    // Total seller platform fee %, split equally **per milestone** (your rule)
    $settings          = \DB::table('platform_settings')->pluck('value', 'key');
    $sellerPlatformPct = (float) ($settings['seller_platform_fee_percent'] ?? 0);
    if ($sellerPlatformPct < 0) $sellerPlatformPct = 0;

    $count   = max(1, (int) $order->milestones()->count());
    $perPct  = $sellerPlatformPct / $count; // e.g. 20% / 3 = 6.666… per milestone

    // All seller math in seller currency
    $gross       = (float) $milestone->price;
    $sellerFee   = round($gross * ($perPct / 100), 2);
    $sellerPay   = round($gross - $sellerFee, 2);

    \DB::transaction(function () use ($order, $milestone, $gross, $sellerPay) {
        // Credit seller
        $seller = $order->seller()->lockForUpdate()->first();
        $seller->increment('wallet', $sellerPay);

        // Mark milestone released
        $milestone->update(['status' => 'released']);

        // ✅ Reduce hold by the milestone **gross**; increase released by the net payout
        $newHold = max(0, round(((float)$order->hold_amount - $gross), 2));
        $order->hold_amount     = $newHold;
        $order->released_amount = round(((float)$order->released_amount + $sellerPay), 2);

        // If all released -> complete
        $unreleased = $order->milestones()->where('status', '!=', 'released')->count();
        if ($unreleased === 0) {
            $order->status = 'completed';
        }
        $order->save();

        // (Optional) balance_transactions insert here...
    });

    return back()->with('success','Hold released to seller.');
}


    public function quote(ServiceOrder $order, Request $request)
    {
        $me = Auth::id();
        abort_unless($me && ($order->buyer_id === $me || $order->seller_id === $me), 403);

        try {
            // Resolve buyer currency/symbol from relations you actually have
            $order->load([
                'buyer:id,country_id',
                'buyer.country:id,currency,currency_symbol',
            ]);

            $buyerCode   = strtoupper($order->buyer->country->currency ?? 'USD');
            $buyerSymbol = $order->buyer->country->currency_symbol ?? '$';

            // Seller/order currency is what the order was created with
            $sellerCode  = strtoupper($order->currency_code ?: 'USD');

            // Convert subtotal to buyer currency
            $fx = new CurrencyConverter(); // uses your cached service
            $buyerSubtotal = round($fx->convert((float) $order->subtotal, $sellerCode, $buyerCode), 2);

            // Fee settings (buyer side)
            $settings = DB::table('platform_settings')->pluck('value','key');
            $buyerPct = (float) ($settings['buyer_platform_fee_percent'] ?? $settings['platform_fee_percent'] ?? 0);
            $gstPct   = (float) ($settings['gst_percent'] ?? 0);
            if ($buyerPct < 0) $buyerPct = 0;
            if ($gstPct   < 0) $gstPct   = 0;

            $buyerFee   = round($buyerSubtotal * ($buyerPct / 100), 2);
            $buyerGST   = round(($buyerSubtotal + $buyerFee) * ($gstPct / 100), 2);
            $buyerTotal = round($buyerSubtotal + $buyerFee + $buyerGST, 2);

            return response()->json([
                'ok'   => true,
                'data' => [
                    'symbol'               => $buyerSymbol,
                    'subtotal'             => $buyerSubtotal,
                    'platform_fee_percent' => $buyerPct,
                    'platform_fee_amount'  => $buyerFee,
                    'gst_percent'          => $gstPct,
                    'gst_amount'           => $buyerGST,
                    'total'                => $buyerTotal,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Failed to load quote.',
            ], 200);
        }
    }
}
