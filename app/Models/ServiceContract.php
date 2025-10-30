<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceContract extends Model
{
    protected $fillable = [
        'seller_id','buyer_id','product_id','subcategory_id','conversation_id',
        'product_title','terms','status',
        'buyer_currency','buyer_platform_fee_percent','buyer_gst_percent','seller_platform_fee_percent',
        'total_price_usd','total_price_buyer',
    ];

    public function milestones()
    {
        return $this->hasMany(ProductMilestone::class, 'contract_id')->orderBy('seq');
    }

    public function order()
    {
        return $this->hasOne(ServiceOrder::class, 'contract_id');
    }

    public function seller(){ return $this->belongsTo(User::class, 'seller_id'); }
    public function buyer(){ return $this->belongsTo(User::class, 'buyer_id'); }

    public function scopeMineAsSeller($q, int $uid){ return $q->where('seller_id',$uid); }
    public function scopeMineAsBuyer($q, int $uid){ return $q->where('buyer_id',$uid); }
}
