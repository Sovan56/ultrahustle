<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductInsight extends Model
{
    protected $fillable = [
        'product_id', 'date', 'views', 'clicks', 'impressions',
    ];

    protected $casts = [
        'date'        => 'date:Y-m-d',
        'views'       => 'integer',
        'clicks'      => 'integer',
        'impressions' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Optional convenience: CTR on the row (clicks / impressions)
    public function getCtrAttribute(): float
    {
        $i = (int) ($this->impressions ?? 0);
        return $i > 0 ? round(($this->clicks ?? 0) / $i, 4) : 0.0;
    }
}
