<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBoostAnalytics extends Model
{
    protected $fillable = [
        'product_boost_id',
        'user_id',
        'type',
        'ip',
    ];
}
