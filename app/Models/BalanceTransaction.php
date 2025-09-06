<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceTransaction extends Model
{
    protected $fillable = [
        'user_id','type','category','amount','currency_symbol','currency_code',
        'gateway','gateway_ref','reference','status','counterparty','payout_account_id','meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta'   => 'array',
    ];

    public function payoutAccount()
    {
        return $this->belongsTo(UserPayoutAccount::class, 'payout_account_id');
    }
}
