<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSubcategory extends Model
{
    protected $fillable = ['product_type_id','name','slug','icon_class','is_active'];

    public function type()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function scopeActive($q) { return $q->where('is_active', 1); }
}
