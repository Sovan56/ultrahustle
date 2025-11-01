<?php

namespace App\Http\Controllers\UserAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\BalanceTransaction;

use App\Models\Product;
use App\Models\ProductBoost;
use App\Models\ProductReview;
use App\Models\ServiceOrder;
use App\Models\ServiceMilestone;
use App\Services\Currency\CurrencyConverter;

use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $me = Auth::id() ?: (int) session('user_id');
        abort_unless($me, 403);

        // ------- SETTINGS (fetch once and reuse) -------
        $settings = DB::table('platform_settings')->pluck('value', 'key');
        $sellerPctTotal = (float) ($settings['seller_platform_fee_percent'] ?? 0);
        if ($sellerPctTotal < 0) $sellerPctTotal = 0;

        // ===== CREATOR KPIs =====
        $activeProducts = Product::where('user_id', $me)->where('status', 'published')->count();

        // earnings(30d): sum of seller NET on released milestones updated in last 30 days
$earnings30d = 0.0;

// Keep ServiceMilestone logic as-is (released milestones updated in last 30 days)
$released = ServiceMilestone::query()
    ->whereHas('order', fn($q) => $q->where('seller_id', $me))
    ->where('status', 'released')
    ->where('updated_at', '>=', now()->subDays(30))
    ->with(['order' => function ($q) {
        $q->withCount('milestones');
    }])
    ->get(['id', 'service_order_id', 'price']);

foreach ($released as $m) {
    $count = max(1, (int) ($m->order->milestones_count ?? 1));
    $perPct = $sellerPctTotal / $count;
    $net = (float) $m->price - ((float) $m->price * ($perPct / 100));
    $earnings30d += round($net, 2);
}

// --- my_orders: fetch only orders for *my products* and in last 30 days ---
// 1) fetch my product ids
$productIds = Product::where('user_id', $me)->pluck('id')->toArray();

if (!empty($productIds)) {
    // 2) fetch my_orders that reference those product ids and are created in last 30 days
    // assumes my_orders has columns: product_id, total_amount, currency_code, created_at
    $myOrders = DB::table('my_orders')
        ->whereIn('product_id', $productIds)
        ->where('created_at', '>=', now()->subDays(30))
        ->get(['id', 'total_amount', 'currency_code']);

    // determine seller display currency (convert my_orders into this currency if needed)
    $seller = \App\Models\User::find($me);
    $seller?->loadMissing('country:id,currency,currency_symbol');
    $sellerCode = strtoupper((string) ($seller->country->currency ?? $seller->currency ?? 'USD'));

    $fx = new CurrencyConverter();

    foreach ($myOrders as $o) {
        $amount = (float) ($o->total_amount ?? 0);
        $orderCode = strtoupper((string) ($o->currency_code ?? 'USD'));

        // convert order amount -> seller currency if needed
        if ($orderCode && $orderCode !== $sellerCode) {
            try {
                $amount = (float) $fx->convert($amount, $orderCode, $sellerCode);
            } catch (\Throwable $e) {
                // conversion failed — keep original amount
            }
        }

        // apply seller platform fee (full order belongs to seller, so use sellerPctTotal)
        $net = $amount - ($amount * ($sellerPctTotal / 100));
        $earnings30d += round($net, 2);
    }
}

$earnings30d = round($earnings30d, 2);
// $earnings30d now contains combined earnings (ServiceMilestone + my_orders) for last 30 days


        $ordersInProgress = ServiceOrder::where('seller_id', $me)
            ->whereIn('status', ['approved_paid', 'in_progress'])
            ->count();

        // avg rating — only reviews on MY products, excluding any review written by me
        $avgRating = null;
        $reviewsCount = 0;
        if (class_exists(ProductReview::class)) {
            $ratingsQ = ProductReview::query()
                ->where('user_id', '!=', $me) // reviewer isn't me
                ->whereHas('product', fn($q) => $q->where('user_id', $me)); // product belongs to me

            $avgVal = (clone $ratingsQ)->avg('rating_number');
            $avgRating = $avgVal ? round($avgVal, 1) : null;
            $reviewsCount = (int) (clone $ratingsQ)->count();
        }

        // ===== CREATOR Lists =====
        $creatorOrders = ServiceOrder::with(['buyer:id,first_name,last_name', 'product:id,name'])
            ->where('seller_id', $me)
            ->latest('id')->limit(10)->get();

        $activeBoosts = ProductBoost::with(['product:id,name,images'])
            ->where('user_id', $me)
            ->where('is_active', 1)
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->orderByDesc('start_at')
            ->get();

        // ===== CLIENT KPIs =====

        // spend(30d) from buyer_quote snapshots paid in last 30d
        $recentPaid = ServiceOrder::where('buyer_id', $me)
            ->whereIn('status', ['approved_paid', 'in_progress', 'completed'])
            ->whereNotNull('meta')
            ->get(['meta']);

        $spend30d = 0.0;
        foreach ($recentPaid as $o) {
            $meta = (array) ($o->meta ?? []);
            $quote = (array) ($meta['buyer_quote'] ?? []);
            $paidAt = isset($quote['paid_at']) ? \Carbon\Carbon::parse($quote['paid_at']) : null;
            if ($paidAt && $paidAt->gte(now()->subDays(30))) {
                $spend30d += (float) ($quote['total'] ?? 0);
            }
        }

        $activeProjects = ServiceOrder::where('buyer_id', $me)
            ->whereIn('status', ['approved_paid', 'in_progress'])
            ->count();

        // (duplicated in original; kept for parity) — determines $me again
        $me = Auth::id() ?: (int) session('user_id');
        abort_unless($me, 403);

        // 1) Courses/Digital (my_orders) — already in buyer currency, no conversion
        $myOrdersSpend = (float) DB::table('my_orders')
            ->where('buyer_id', $me)
            ->sum('total_amount');

        // 2) Services — sum what the **buyer actually pays**
        $buyer = Auth::user() ?? \App\Models\User::find($me);
        $buyer?->loadMissing('country:id,currency,currency_symbol');

        $userCode   = strtoupper((string)($buyer->country->currency ?? $buyer->currency ?? 'USD'));
        $userSymbol = (string)($buyer->country->currency_symbol ?? '$');

        $fx = new CurrencyConverter();
        $buyerPct = max(0, (float) ($settings['buyer_platform_fee_percent'] ?? $settings['platform_fee_percent'] ?? 0));
        $gstPct   = max(0, (float) ($settings['gst_percent'] ?? 0));

        $orders = ServiceOrder::query()
            ->where('buyer_id', $me)
            ->where('status', 'completed')
            ->with(['buyer:id,country_id', 'buyer.country:id,currency,currency_symbol'])
            ->get(['id', 'currency_code', 'subtotal', 'platform_fee_amount', 'gst_amount', 'total_payable', 'meta']);

        $serviceSpend = 0.0;

        foreach ($orders as $o) {
            $meta   = (array) ($o->meta ?? []);
            $quote  = (array) ($meta['buyer_quote'] ?? []);

            if (isset($quote['total'])) {
                // Use the approved snapshot in buyer currency (at time of payment)
                $qTotal   = (float) $quote['total'];
                $qCode    = strtoupper((string) ($quote['currency_code'] ?? ''));
                // If snapshot currency differs from current user currency, convert now
                if ($qCode && $qCode !== $userCode) {
                    try {
                        $qTotal = (float) $fx->convert($qTotal, $qCode, $userCode);
                    } catch (\Throwable $e) {
                    }
                }
                $serviceSpend += $qTotal;
                continue;
            }

            // No snapshot: compute a live buyer-side preview like SP::quote
            $sellerCode = strtoupper((string) ($o->currency_code ?: 'USD'));
            $buyerCode  = strtoupper((string) ($o->buyer->country->currency ?? 'USD'));

            $buyerSubtotal = (float) $o->subtotal;
            if ($sellerCode !== $buyerCode) {
                try {
                    $buyerSubtotal = (float) $fx->convert($buyerSubtotal, $sellerCode, $buyerCode);
                } catch (\Throwable $e) {
                }
            }

            $buyerFee   = round($buyerSubtotal * ($buyerPct / 100), 2);
            $buyerGST   = round(($buyerSubtotal + $buyerFee) * ($gstPct / 100), 2);
            $buyerTotal = round($buyerSubtotal + $buyerFee + $buyerGST, 2);

            // If preview currency (buyerCode) still differs from current user currency, convert
            if ($buyerCode !== $userCode) {
                try {
                    $buyerTotal = (float) $fx->convert($buyerTotal, $buyerCode, $userCode);
                } catch (\Throwable $e) {
                }
            }

            $serviceSpend += $buyerTotal;
        }

        $spendTotal = round($myOrdersSpend + $serviceSpend, 2);

        // open milestones == in_progress orders (your definition)
        $openMilestones = ServiceOrder::where('buyer_id', $me)
            ->whereIn('status', ['approved_paid', 'in_progress', 'reupdated'])
            ->count();

        // ===== CLIENT Lists =====
        $clientProjects = ServiceOrder::with([
            'seller:id,first_name,last_name',
            'product:id,name',
            'buyer:id,country_id',
            'buyer.country:id,currency,currency_symbol',
        ])
            ->where('buyer_id', $me)
            ->latest('id')->limit(10)->get();

        // ----- Compute buyer-currency "Budget" per project (client table) -----
        try {
            $fx = new CurrencyConverter();

            // fee settings (buyer side), same as in SP::quote
            $buyerPct = (float) ($settings['buyer_platform_fee_percent'] ?? $settings['platform_fee_percent'] ?? 0);
            $gstPct   = (float) ($settings['gst_percent'] ?? 0);
            if ($buyerPct < 0) $buyerPct = 0;
            if ($gstPct   < 0) $gstPct   = 0;

            foreach ($clientProjects as $o) {
                // If we already have a paid buyer_quote snapshot → use it
                $meta   = (array) ($o->meta ?? []);
                $quote  = (array) ($meta['buyer_quote'] ?? []);
                $qTotal = isset($quote['total']) ? (float) $quote['total'] : null;
                $qSym   = $quote['currency_symbol'] ?? null;

                if ($qTotal !== null && $qTotal >= 0 && $qSym) {
                    // attach computed fields for blade
                    $o->setAttribute('budget_total',  round($qTotal, 2));
                    $o->setAttribute('budget_symbol', (string) $qSym);
                    continue;
                }

                // Otherwise do a live preview conversion like SP::quote
                $buyerCode   = strtoupper($o->buyer->country->currency ?? 'USD');
                $buyerSymbol = $o->buyer->country->currency_symbol ?? '$';
                $sellerCode  = strtoupper($o->currency_code ?: 'USD');

                $subtotalSeller = (float) ($o->subtotal ?? 0);

                $buyerSubtotal = $subtotalSeller;
                if ($sellerCode !== $buyerCode) {
                    try {
                        $buyerSubtotal = (float) $fx->convert($subtotalSeller, $sellerCode, $buyerCode);
                    } catch (\Throwable $e) {
                        // keep as-is if conversion fails
                    }
                }

                $buyerFee   = round($buyerSubtotal * ($buyerPct / 100), 2);
                $buyerGST   = round(($buyerSubtotal + $buyerFee) * ($gstPct / 100), 2);
                $buyerTotal = round($buyerSubtotal + $buyerFee + $buyerGST, 2);

                $o->setAttribute('budget_total',  $buyerTotal);
                $o->setAttribute('budget_symbol', $buyerSymbol);
            }
        } catch (\Throwable $e) {
            // If something fails, leave fields unset; Blade will fallback to zeros
        }

        $clientMilestones = ServiceMilestone::with(['order:id,seller_id,buyer_id,product_id,status', 'order.product:id,name'])
            ->whereHas('order', fn($q) => $q->where('buyer_id', $me))
            ->latest('id')->limit(10)->get();

        // ----- Client Transactions (latest first) -----
        $transactions = BalanceTransaction::query()
            ->where('user_id', $me)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id','type','amount','currency_symbol','gateway','gateway_ref','status','meta','created_at']);

        // Package for the Blade
        return view('UserAdmin.index', [
            'kpi' => [
                'creator' => [
                    'activeProducts'   => (int) $activeProducts,
                    'earnings30d'      => round($earnings30d, 2),
                    'ordersInProgress' => (int) $ordersInProgress,
                    'avgRating'        => $avgRating,         // average across reviews on my products, excluding my own reviews
                    'reviewsCount'     => $reviewsCount,      // total reviews counted in the average (added; safe extra)
                ],
                'client' => [
                    'activeProjects' => (int) $activeProjects,
                    'spendTotal'     => round($spendTotal, 2),   // <-- unchanged key as requested
                    'openMilestones' => (int) $openMilestones,
                ],
            ],
            'currency' => [
                'code'   => $userCode,
                'symbol' => $userSymbol,
            ],
            'creator' => [
                'orders' => $creatorOrders,
                'boosts' => $activeBoosts,
            ],
            'client' => [
                'projects'     => $clientProjects,
                'milestones'   => $clientMilestones,
                'transactions' => $transactions,
            ],
        ]);
    }
}
