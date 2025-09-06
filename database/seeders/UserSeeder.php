<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example user #1
        User::create([
            'first_name'   => 'Sovan',
            'last_name'    => 'Majumder',
            'phone_number' => '9876543210',
            'email'        => 'sovanr044@gmail.com',
            'country_id'   => 103, // Replace with a valid country ID
            'currency'     => 'INR',
            'wallet'       => 2000,
            'password'     => Hash::make('123'),
        ]);

        // You can add more sample users as needed...
    }
}
