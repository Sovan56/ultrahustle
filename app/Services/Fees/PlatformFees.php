<?php

namespace App\Services\Fees;

use Illuminate\Support\Facades\DB;

class PlatformFees
{
    public static function get(string $key, $default = null)
    {
        return DB::table('platform_settings')->where('key', $key)->value('value') ?? $default;
    }

    public static function buyerFeePercent(): float
    {
        return (float) self::get('buyer_platform_fee_percent', 5.0);
    }

    public static function sellerFeePercent(): float
    {
        return (float) self::get('seller_platform_fee_percent', 10.0);
    }

    public static function gstPercent(): float
    {
        return (float) self::get('gst_percent', 0.0);
    }
}
