<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure wallets exist for demo seller (id=1) and a demo buyer (id=2)
        Wallet::firstOrCreate(['user_id' => 1], ['available_balance' => 0, 'hold_balance' => 0]);
        Wallet::firstOrCreate(['user_id' => 2], ['available_balance' => 0, 'hold_balance' => 0]);
    }
}
