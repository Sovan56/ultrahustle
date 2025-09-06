<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFaq extends Model
{
    protected $fillable = ['product_id', 'faq_heading', 'question', 'faq_answer'];
    public function product(){ return $this->belongsTo(Product::class); }
    public function faqs()
{
    return $this->hasMany(ProductFaq::class, 'product_id')->orderBy('id');
}

}
