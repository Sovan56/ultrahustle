<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    protected $fillable = ['name','slug','is_active'];
    public function subcategories(){ return $this->hasMany(ProductSubcategory::class); }

    public function scopeActive($q) { return $q->where('is_active', 1); }
}
