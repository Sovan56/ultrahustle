<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamMembersSeeder extends Seeder
{
    public function run(): void
    {
        $alphaId = DB::table('user_admin_teams')->where('team_name', 'Team Alpha')->value('id');

        DB::table('team_members')->insert([
            [
                'team_id'      => $alphaId,
                'positions'    => 'Designer',
                'role'         => 'admin',
                'member_id'    => 'U2',
                'member_email' => 'joined@example.com',
                'status'       => 'accepted',
                'invite_token' => null,
                'invited_at'   => now()->subDays(3),
                'responded_at' => now()->subDays(2),
                'invited_by'   => 'U1',
                'created_at'   => now()->subDays(3),
                'updated_at'   => now()->subDays(2),
            ],
            [
                'team_id'      => $alphaId,
                'positions'    => 'QA',
                'role'         => 'user',
                'member_id'    => null,
                'member_email' => 'pending@example.com',
                'status'       => 'pending',
                'invite_token' => Str::random(40),
                'invited_at'   => now()->subDay(),
                'responded_at' => null,
                'invited_by'   => 'U1',
                'created_at'   => now()->subDay(),
                'updated_at'   => now()->subDay(),
            ],
        ]);
    }
}
