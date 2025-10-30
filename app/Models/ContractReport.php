<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractReport extends Model
{
    protected $fillable = ['service_contract_id','reporter_id','role','reason','status','meta'];
    protected $casts = ['meta' => 'array'];

    public function contract(){ return $this->belongsTo(ServiceContract::class, 'service_contract_id'); }
}
