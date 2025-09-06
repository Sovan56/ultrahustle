<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

class Product extends Model
{
    protected $fillable = [
    'user_id','product_type_id','product_subcategory_id','country_id',
    'name', 'urls', 'uses_ai','has_team','description','images','files','status','is_boosted'
];

protected $casts = [
    'images' => 'array',
    'urls' => 'array',
    'files'  => 'array',
    'uses_ai' => 'boolean',
    'has_team'=> 'boolean',
    'is_boosted' => 'boolean',
];

protected $appends = ['seller_name'];

    public function type()        { return $this->belongsTo(ProductType::class, 'product_type_id'); }
    public function subcategory() { return $this->belongsTo(ProductSubcategory::class, 'product_subcategory_id'); }
    public function pricings()    { return $this->hasMany(ProductPricing::class); }
    public function faqs()
{
    // Oldest first; change to ->orderByDesc('id') if you want newest first
    return $this->hasMany(\App\Models\ProductFaq::class, 'product_id')->orderBy('id');
}

    public function boosts()      { return $this->hasMany(ProductBoost::class); }
    public function orders()      { return $this->hasMany(ProductOrder::class, 'product_id'); }

    public function reviews()     { return $this->hasMany(ProductReview::class); }
    public function user()        { return $this->belongsTo(User::class, 'user_id'); }

    public function seller()
{
    // Your products.user_id currently may be string — Eloquent can still relate.
    return $this->belongsTo(\App\Models\User::class, 'user_id');
}

    // app/Models/Product.php (add this)
public function country()
{
    return $this->belongsTo(\App\Models\Country::class);
}

// app/Models/Product.php
public function scopeActiveBoosted($q)
{
    $now = now();

    $q->whereHas('boosts', function ($b) use ($now) {
        $b->where('start_at', '<=', $now);

        // If you have `end_at` column:
        if (Schema::hasColumn('product_boosts', 'end_at')) {
            $b->where('end_at', '>=', $now);
        }

        // If instead you have `duration` column (in hours or days):
        if (Schema::hasColumn('product_boosts', 'duration')) {
            $b->whereRaw("DATE_ADD(start_at, INTERVAL duration DAY) >= ?", [$now]);
        }
    });
}

    // Trust filters
    public function scopeTeam($q)      { return $q->where('team', 1); }
    public function scopeUsesAi($q)    { return $q->where('uses_ai', 1); }

    // app/Models/Product.php
public function scopeBoostedNow($q)
    {
        $now = Carbon::now();
        return $q->where('status', 'published')
                 ->whereHas('boosts', function ($b) use ($now) {
                     $b->where('is_active', 1)
                       ->where('start_at', '<=', $now)
                       ->where('end_at', '>=', $now);
                 });
    }

    // Helper: pick a display image
    public function displayImage(): string
    {
        $arr = is_array($this->images) ? $this->images : [];
        return $arr[0] ?? asset('images/placeholder-product.png');
    }

    // Helper: choose the lowest tier price (Basic/Standard/Premium if present)
    public function lowestPrice(?int $countryId = null): ?array
    {
        $q = $this->pricings();
        // If your pricing is country-specific, uncomment next line.
        // if ($countryId) $q->where('country_id', $countryId);
        $rows = $q->get();
        if ($rows->isEmpty()) return null;

        $order = ['basic'=>0, 'standard'=>1, 'premium'=>2];
        $chosen = $rows->sort(function($a,$b) use($order){
            return ($order[$a->tier] ?? 9) <=> ($order[$b->tier] ?? 9)
                ?: ($a->amount <=> $b->amount);
        })->first();

        return [
            'amount'       => (float) $chosen->amount,
            'currency'     => $chosen->currency?->code ?? 'USD', // adjust if you store code directly
            'tier'         => $chosen->tier,
            'pricing_id'   => $chosen->id,
        ];
    }

    /** Accessors */
    public function getSellerNameAttribute(): string
    {
        $u = $this->user;
        if (!$u) return 'Seller';
        $first = trim((string)($u->first_name ?? ''));
        $last  = trim((string)($u->last_name  ?? ''));
        $full  = trim($first . ' ' . $last);
        if ($full !== '') return $full;
        // fallback to a generic "name" column if you have one
        return (string)($u->name ?? 'Seller');
    }


}
