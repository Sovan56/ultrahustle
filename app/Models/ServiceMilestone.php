<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceMilestone extends Model
{
    protected $fillable = [
        'service_order_id','title','description','price','start_date','end_date','status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function order() { return $this->belongsTo(ServiceOrder::class, 'service_order_id'); }
    public function submissions() { return $this->hasMany(ServiceMilestoneSubmission::class); }

    public function canSellerEdit(int $sellerId): bool
    {
        return $this->order && $this->order->seller_id === $sellerId && in_array($this->status, ['draft'], true);
    }
}
