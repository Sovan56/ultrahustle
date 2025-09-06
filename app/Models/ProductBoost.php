<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBoost extends Model
{
    protected $fillable = [
        'product_id','user_id','country_id','country_code',
        'days','start_at','end_at','amount','is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_at'  => 'datetime',
        'end_at'    => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
