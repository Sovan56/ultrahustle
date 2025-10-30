<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMilestone extends Model
{
    protected $fillable = [
        'contract_id','seq','title','description','price_usd',
        'start_date','end_date','status','deliverable_path','deliverable_url','confirmed_at'
    ];

    public function contract(){ return $this->belongsTo(ServiceContract::class, 'contract_id'); }

    public function isEditableBySeller(): bool
    {
        // editable only while contract pending/draft AND milestone is draft
        return $this->status === 'draft' && in_array($this->contract->status, ['pending','draft']);
    }
}
