<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyOrder extends Model
{
    protected $fillable = [
        'buyer_id','product_id','product_type_id','tier',
        'base_amount','platform_fee_percent','platform_fee_amount',
        'gst_percent','gst_amount','total_amount','currency',
        'wallet_txn_id','paid_at','status','delivery_files','course_urls','meta'
    ];

    protected $casts = [
        'delivery_files' => 'array',
        'course_urls'    => 'array',
        'meta'           => 'array',
        'paid_at'        => 'datetime',
    ];

    public function product()   { return $this->belongsTo(\App\Models\Product::class); }
    public function buyer()     { return $this->belongsTo(\App\Models\User::class, 'buyer_id'); }
}
