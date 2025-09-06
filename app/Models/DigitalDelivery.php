<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalDelivery extends Model
{
    protected $fillable = ['order_id','file_path','sent_at'];
    protected $casts = ['sent_at'=>'datetime'];

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }
}
