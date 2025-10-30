<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Mail\Service\ReportFiledMail;
use App\Models\ServiceOrder;
use App\Models\ServiceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ServiceReportController extends Controller
{
    public function file(Request $request, ServiceOrder $order)
    {
        $me = Auth::id();
        abort_unless($me && ($order->buyer_id === $me || $order->seller_id === $me), 403);

        $data = $request->validate([
            'reason' => ['required','string','max:4000'],
        ]);

        $role = $order->buyer_id === $me ? 'buyer' : 'seller';

        $report = ServiceReport::create([
            'service_order_id' => $order->id,
            'reporter_id'      => $me,
            'role'             => $role,
            'reason'           => $data['reason'],
            'status'           => 'open',
        ]);

        $order->update(['status' => 'dispute_open']);

        try {
            // notify admin (you can add admin email env)
            $adminMail = config('mail.from.address');
            Mail::to($adminMail)->queue(new ReportFiledMail($report));
        } catch (\Throwable $e) {}

        return back()->with('success','Report submitted for admin review.');
    }
}
