<?php

namespace App\Support;

use App\Models\Country;
use App\Models\User;

class Currency
{
    /** ISO code for a user (prefer user's country currency, fallback to user->currency, else USD) */
    public static function codeForUser(?User $user): string
    {
        if (!$user) return 'USD';
        if ($user->country_id) {
            $code = Country::where('id', $user->country_id)->value('currency');
            if ($code) return $code;
        }
        return $user->currency ?: 'USD';
    }

    /** Symbol from countries table; fallback to code if missing */
    public static function symbol(string $code): string
    {
        $sym = Country::where('currency', strtoupper(trim($code)))->value('currency_symbol');
        return $sym ?: strtoupper(trim($code));
    }
}
