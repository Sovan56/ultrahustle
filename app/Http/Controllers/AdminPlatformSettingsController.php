<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;

class AdminPlatformSettingsController extends Controller
{
    public function show() {
        return view('admin.settings.fees', [
            'platform_fee_percent'      => (float)(PlatformSetting::get('platform_fee_percent', 5) ?? 5),
            'gst_percent'               => (float)(PlatformSetting::get('gst_percent', 18) ?? 18),
            'seller_platform_fee_percent' => (float)(PlatformSetting::get('seller_platform_fee_percent', 10) ?? 10),
            'dispute_buyer_refund_percent' => (float)(PlatformSetting::get('dispute_buyer_refund_percent', 10) ?? 10),
        ]);
    }

    public function save(Request $r) {
        $r->validate([
            'platform_fee_percent'       => 'required|numeric|min:0|max:50',
            'gst_percent'                => 'required|numeric|min:0|max:50',
            'seller_platform_fee_percent'=> 'required|numeric|min:0|max:50',
            'dispute_buyer_refund_percent'=> 'required|numeric|min:0|max:50',
        ]);

        PlatformSetting::set('platform_fee_percent', (string)$r->platform_fee_percent);
        PlatformSetting::set('gst_percent', (string)$r->gst_percent);
        PlatformSetting::set('seller_platform_fee_percent', (string)$r->seller_platform_fee_percent);
        PlatformSetting::set('dispute_buyer_refund_percent', (string)$r->dispute_buyer_refund_percent);

        return back()->with('success','Saved.');
    }
}
