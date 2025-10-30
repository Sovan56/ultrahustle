<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceReport extends Model
{
    protected $fillable = [
        'service_order_id','reporter_id','role','reason','evidence','status'
    ];

    protected $casts = [
        'evidence' => 'array',
    ];

    public function order() { return $this->belongsTo(ServiceOrder::class, 'service_order_id'); }
    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
}
