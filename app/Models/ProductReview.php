<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    // Table name is optional if it matches, but it doesn't hurt:
    protected $table = 'product_reviews';

    // Allow the fields you create() with
    protected $fillable = [
        'product_id',
        'user_id',
        'rating_number',
        'review',
        'images',      // casted to array below; stored as JSON in DB
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
