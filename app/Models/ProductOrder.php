<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOrder extends Model
{
    protected $table = 'product_orders';

    protected $fillable = [
        // keep your actual columns; examples:
        'product_id',
        'buyer_name',
        'amount',
        'currency',
        'currency_code',
        'status',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stages(): HasMany
    {
        // ALWAYS read from product_order_stages, ordered by position
        return $this->hasMany(ProductOrderStage::class, 'order_id')->orderBy('position', 'asc');
    }
}
