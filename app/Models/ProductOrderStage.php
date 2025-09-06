<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOrderStage extends Model
{
    protected $table = 'product_order_stages';

    protected $fillable = [
        'order_id',
        'position',
        'title',
        'notes',
        'status', // 'pending' | 'in_progress' | 'done'
    ];

    protected $casts = [
        'order_id' => 'string', // supports int or uuid string
        'position' => 'int',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }
}
