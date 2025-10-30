<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrderMilestone extends Model
{
    protected $fillable = ['service_order_id','milestone_id','status','hold_amount','released_at','cancelled_at'];

    protected $dates = ['released_at','cancelled_at'];

    public function order(){ return $this->belongsTo(ServiceOrder::class); }
    public function milestone(){ return $this->belongsTo(ProductMilestone::class, 'milestone_id'); }
}
