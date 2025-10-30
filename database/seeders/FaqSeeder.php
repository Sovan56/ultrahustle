<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'quote'            => 'Ultra Hustle saved me weeks of searching. I hired a designer and a content writer in under 48 hours, and the milestone-based payments gave me complete peace of mind.',
                'author_name'      => 'Ananya Verma',
                'author_role'      => 'Startup Founder',
                'author_location'  => 'India',
                'is_active'        => 1,
                'sort_order'       => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'quote'            => 'The platform feels much simpler than Upwork or Fiverr. I like the trust badge system — I knew exactly which freelancer I could rely on for a high-stakes campaign.',
                'author_name'      => 'David Mitchell',
                'author_role'      => 'Marketing Manager',
                'author_location'  => 'USA',
                'is_active'        => 1,
                'sort_order'       => 2,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'quote'            => 'The milestone escrow is a game-changer. I never have to worry about payment delays, and the dashboard shows me exactly where my projects stand.',
                'author_name'      => 'James Rodriguez',
                'author_role'      => 'Web Developer',
                'author_location'  => 'Canada',
                'is_active'        => 1,
                'sort_order'       => 3,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'quote'            => 'Hosting my video course directly on Ultra Hustle was seamless. I didn’t need separate tools for payments, hosting, or student management.',
                'author_name'      => 'Omar Ali',
                'author_role'      => 'Course Creator',
                'author_location'  => 'UK',
                'is_active'        => 1,
                'sort_order'       => 4,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ];

        DB::table('faqs')->upsert($rows, ['quote', 'author_name'], ['updated_at']);
    }
}
