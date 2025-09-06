<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPayoutAccount extends Model
{
    protected $fillable = [
        'user_id','type','holder_name','account_number','ifsc','bank_name','branch','upi_vpa','paypal_email','is_default','meta'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'meta'       => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper for masked account number
    public function maskedAccount(): string
    {
        $acc = (string)($this->account_number ?? '');
        if (!$acc) return '';
        return str_repeat('X', max(0, strlen($acc) - 4)) . substr($acc, -4);
    }
}
