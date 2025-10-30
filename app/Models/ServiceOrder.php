<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    protected $fillable = [
        'buyer_id','seller_id','product_id',
        'subcategory_id','product_subcategory_id',
        'terms','meta',
        'currency_code','currency_symbol',
        'subtotal','platform_fee_percent','platform_fee_amount','gst_percent','gst_amount','total_payable',
        'hold_amount','released_amount',
        'base_currency_code','base_subtotal','base_total_payable','base_hold_amount','base_released_amount',
        'status',
    ];

    protected $casts = [
        'meta' => 'array',
        'subtotal' => 'decimal:2',
        'platform_fee_percent' => 'decimal:3',
        'platform_fee_amount' => 'decimal:2',
        'gst_percent' => 'decimal:3',
        'gst_amount' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'hold_amount' => 'decimal:2',
        'released_amount' => 'decimal:2',
        'base_subtotal' => 'decimal:2',
        'base_total_payable' => 'decimal:2',
        'base_hold_amount' => 'decimal:2',
        'base_released_amount' => 'decimal:2',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function milestones()
    {
        return $this->hasMany(ServiceMilestone::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /** Missing relation used by the orders table/view */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Optional helper if you need to access the subcategory directly */
    public function subcategory()
    {
        return $this->belongsTo(ProductSubcategory::class, 'product_subcategory_id');
    }

    // ── Scopes / helpers ────────────────────────────────────────────────────

    public function scopeForUser($q, $uid)
    {
        return $q->where(function($w) use ($uid) {
            $w->where('buyer_id', $uid)->orWhere('seller_id', $uid);
        });
    }

    public function isEditableBySeller(int $sellerId): bool
    {
        return $this->seller_id === $sellerId && in_array($this->status, ['draft','sent','reupdated'], true);
    }
}
