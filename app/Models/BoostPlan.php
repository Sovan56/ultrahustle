<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoostPlan extends Model
{
    protected $fillable = [
        'name', 'days', 'price_usd', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'days'      => 'integer',
        'price_usd' => 'decimal:2',
    ];
}
