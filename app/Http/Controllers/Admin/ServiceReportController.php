<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\ServiceReport;
use App\Models\ServiceMilestone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ServiceReportController extends Controller
{
    public function index(Request $request)
    {
        // Filter: status=open/approved/rejected or all
        $status = $request->query('status');
        $q = ServiceReport::query()
            ->with([
                'reporter:id,first_name,last_name,email',
                'order:id,buyer_id,seller_id,currency_code,currency_symbol,subtotal,hold_amount,released_amount,status',
                'order.buyer:id,first_name,last_name,email',
                'order.seller:id,first_name,last_name,email',
            ])
            ->latest('id');

        if ($status && in_array($status, ['open','approved','rejected'], true)) {
            $q->where('status', $status);
        }

        $reports = $q->paginate(25);

        return view('admin.service.reports.index', compact('reports', 'status'));
    }

    public function show(ServiceReport $report)
    {
        $report->load([
            'reporter:id,first_name,last_name,email',
            'order.milestones' => function ($q) { $q->orderBy('id'); },
            'order.buyer:id,first_name,last_name,email,wallet,country_id',
            'order.buyer.country:id,name,currency,currency_symbol',
            'order.seller:id,first_name,last_name,email,wallet,country_id',
            'order.seller.country:id,name,currency,currency_symbol',
        ]);

        $order = $report->order;

        // Compute per-milestone fee percent snapshot for preview
        $ps = DB::table('platform_settings')->pluck('value', 'key');
        $sellerFeeTotalPct = (float) ($ps['seller_platform_fee_percent'] ?? $ps['platform_fee_percent'] ?? 0);
        $msCount = max(1, (int) ($order->milestones->count()));
        $perMsPct = $sellerFeeTotalPct / $msCount;

        // Buyer refund fee (when buyer reports against a seller cancel) – you said 20%; fallback to same setting
        $buyerRefundPct = (float) ($ps['buyer_refund_percent'] ?? $sellerFeeTotalPct ?? 20);

        // Buyer-currency convenience (for labels)
        $buyerCode   = strtoupper($order->buyer->country->currency ?? 'USD');
        $buyerSymbol = $order->buyer->country->currency_symbol ?? '$';
        $sellerCode  = strtoupper($order->currency_code ?: 'USD');

        $fx = null;
        try { $fx = app(\App\Services\Currency\CurrencyConverter::class); } catch (\Throwable $e) {}

        $holdSeller  = (float) ($order->hold_amount ?? 0);
        $holdBuyer   = $fx ? round($fx->convert($holdSeller, $sellerCode, $buyerCode), 2) : $holdSeller;

        // Find "last submitted" milestone for potential seller approval settlement
        $lastSubmitted = $order->milestones->where('status', 'submitted')->sortByDesc('id')->first();
        $lastSubmittedGross = $lastSubmitted ? (float) $lastSubmitted->price : 0.0;
        $lastSubmittedNet   = $lastSubmittedGross * (1 - ($perMsPct / 100));

        return view('admin.service.reports.show', [
            'report'              => $report,
            'order'               => $order,
            'perMsPct'            => $perMsPct,
            'sellerFeeTotalPct'   => $sellerFeeTotalPct,
            'buyerRefundPct'      => $buyerRefundPct,
            'buyerSymbol'         => $buyerSymbol,
            'holdSeller'          => $holdSeller,
            'holdBuyer'           => $holdBuyer,
            'lastSubmitted'       => $lastSubmitted,
            'lastSubmittedGross'  => $lastSubmittedGross,
            'lastSubmittedNet'    => $lastSubmittedNet,
        ]);
    }

    public function approve(Request $request, ServiceReport $report)
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($report->status !== 'open') {
            return back()->withErrors(['report' => 'This report has already been processed.']);
        }

        $report->load(['order.milestones', 'order.buyer.country', 'order.seller.country', 'reporter']);
        $order = $report->order;

        $ps = DB::table('platform_settings')->pluck('value', 'key');
        $sellerFeeTotalPct = (float) ($ps['seller_platform_fee_percent'] ?? $ps['platform_fee_percent'] ?? 0);
        $buyerRefundPct    = (float) ($ps['buyer_refund_percent'] ?? $sellerFeeTotalPct ?? 20);

        $msCount = max(1, (int) $order->milestones()->count());
        $perMsPct = $sellerFeeTotalPct / $msCount;

        $sellerCode  = strtoupper($order->currency_code ?: 'USD');
        $buyerCode   = strtoupper($order->buyer->country->currency ?? 'USD');
        $fx = null; try { $fx = app(\App\Services\Currency\CurrencyConverter::class); } catch (\Throwable $e) {}

        DB::transaction(function () use ($request, $report, $order, $perMsPct, $buyerRefundPct, $sellerCode, $buyerCode, $fx) {
            $role = $report->role; // 'buyer' or 'seller'

            $settlement = [
                'role'      => $role,
                'percent'   => null,
                'gross'     => 0.0,
                'net'       => 0.0,
                'currency'  => $order->currency_code,
                'details'   => [],
            ];

            if ($role === 'buyer') {
                // Case 1: Buyer reported (e.g., seller canceled). Refund remaining HOLD minus X%.
                $hold = (float) ($order->hold_amount ?? 0);
                if ($hold <= 0) {
                    throw new \RuntimeException('No hold amount available to refund.');
                }

                $settlement['percent'] = $buyerRefundPct;
                $netSellerCcy = round($hold * (1 - ($buyerRefundPct / 100)), 2);  // seller currency (order currency)
                $settlement['gross'] = $hold;
                $settlement['net']   = $netSellerCcy;

                // Decrease hold completely
                $order->hold_amount = 0.0;
                $order->status = 'dispute_resolved_refund';
                $order->save();

                // Credit buyer wallet in buyer currency
                $netBuyerCcy = $fx ? round($fx->convert($netSellerCcy, $sellerCode, $buyerCode), 2) : $netSellerCcy;
                DB::table('users')->where('id', $order->buyer_id)->update([
                    'wallet' => DB::raw('wallet + ' . number_format($netBuyerCcy, 2, '.', ''))
                ]);

                $settlement['details'] = [
                    'refund_to_buyer_buyer_ccy' => $netBuyerCcy,
                    'refund_percent'            => $buyerRefundPct,
                    'hold_before'               => $hold,
                    'hold_after'                => 0.0,
                ];
            } else {
                // Case 2: Seller reported (e.g., buyer canceled). Pay for LAST SUBMITTED milestone minus per-ms fee.
                $lastSubmitted = $order->milestones()
                    ->where('status', 'submitted')
                    ->orderByDesc('id')
                    ->first();

                if (!$lastSubmitted) {
                    throw new \RuntimeException('No submitted milestone found to settle.');
                }

                $gross = (float) ($lastSubmitted->price ?? 0);
                $net   = round($gross * (1 - ($perMsPct / 100)), 2);

                // Consume from hold, add to released (gross accounting), update milestone state
                $order->hold_amount      = round(max(0, (float)$order->hold_amount - $gross), 2);
                $order->released_amount  = round((float)$order->released_amount + $gross, 2);
                $order->status           = 'dispute_resolved_release';
                $order->save();

                $lastSubmitted->update(['status' => 'approved']); // mark settled

                // Credit seller wallet in seller/order currency
                DB::table('users')->where('id', $order->seller_id)->update([
                    'wallet' => DB::raw('wallet + ' . number_format($net, 2, '.', ''))
                ]);

                $settlement['percent'] = $perMsPct;
                $settlement['gross']   = $gross;
                $settlement['net']     = $net;
                $settlement['details'] = [
                    'milestone_id'    => $lastSubmitted->id,
                    'per_milestone_%' => $perMsPct,
                ];
            }

            // Close report
            $report->status = 'approved';
            $report->resolution_note = $request->input('note');
            $snap = (array) ($report->settlement_snapshot ?? []);
            $snap[] = $settlement;
            $report->settlement_snapshot = $snap;
            $report->save();
        });

        // Notify both parties (wrap in try/catch so UI never breaks)
        try {
            // You can replace these with your Mailable classes if you have them
             Mail::to($report->reporter->email)->send(new \App\Mail\Service\ReportApprovedMail($report));
             Mail::to(optional($report->order->buyer)->email)->send(new \App\Mail\Service\ReportApprovedMail($report));
            Mail::to(optional($report->order->seller)->email)->send(new \App\Mail\Service\ReportApprovedMail($report));
        } catch (\Throwable $e) {}

        return back()->with('success', 'Report approved and settlement applied.');
    }

    public function reject(Request $request, ServiceReport $report)
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($report->status !== 'open') {
            return back()->withErrors(['report' => 'This report has already been processed.']);
        }

        $report->status = 'rejected';
        $report->resolution_note = $request->input('note');
        $snap = (array) ($report->settlement_snapshot ?? []);
        $snap[] = ['role' => $report->role, 'action' => 'rejected', 'note' => $report->resolution_note];
        $report->settlement_snapshot = $snap;
        $report->save();

        // Notify reporter that the report was rejected (and optionally the other party)
        try {
            Mail::to($report->reporter->email)->send(new \App\Mail\Service\ReportRejectedMail($report));
        } catch (\Throwable $e) {}

        return back()->with('success', 'Report rejected.');
    }
}
