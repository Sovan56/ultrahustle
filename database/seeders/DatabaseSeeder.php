<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            UserAdminAnotherDetailsSeeder::class,
            UserAdminTeamsSeeder::class,
            CountriesTableSeeder::class,
            TeamMembersSeeder::class,
            ProductTypeSeeder::class,
            SubcategorySeeder::class,
            SampleProductsSeeder::class,
            WalletSeeder::class,
            AdminSeeder::class,
            BoostPlanSeeder::class,
        ]);
    }
}
