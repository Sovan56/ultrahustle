<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserAdminAnotherDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('user_admin_another_details')->insert([
            [
                'user_admin_id'      => '50239522',
                'profile_picture'    => 'profile_50239522.jpg',
                'social_media_link'  => json_encode([
                    'facebook' => 'https://facebook.com/user50239522',
                    'twitter'  => 'https://twitter.com/user50239522',
                    'linkedin' => 'https://linkedin.com/in/user50239522',
                ]),
                'profile_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. This is a sample profile description for user 50239522.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_admin_id'      => '01K25RF9Q',
                'profile_picture'    => 'profile_01K25RF9Q.png',
                'social_media_link'  => json_encode([
                    'facebook' => 'https://facebook.com/user01K25RF9Q',
                    'instagram'=> 'https://instagram.com/user01K25RF9Q',
                    'linkedin' => 'https://linkedin.com/in/user01K25RF9Q',
                ]),
                'profile_description' => 'Another example profile description for testing purposes for user 01K25RF9Q.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
