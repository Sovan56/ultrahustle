<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserAdminTeamsSeeder extends Seeder
{
    public function run(): void
    {
        // assume session user  -> but for seed we fake an owner id "U1"
        DB::table('user_admin_teams')->insert([
            [
                'team_name'     => 'Team Alpha',
                'team_owner_id' => 'U1',
                'profile_image' => 'https://placehold.co/40x40.png?text=Img',
                'about'         => 'Hello',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'team_name'     => 'Team Beta',
                'team_owner_id' => 'U1',
                'profile_image' => 'https://placehold.co/40x40.png?text=Img',
                'about'         => 'Hello',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);
    }
}
