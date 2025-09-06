<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name'=>'Digital Product', 'slug'=>'digital-product', 'is_active'=>1],
            ['name'=>'Course',          'slug'=>'course',          'is_active'=>1],
            ['name'=>'Services',        'slug'=>'services',        'is_active'=>1],
        ];

        foreach ($types as $t) {
            DB::table('product_types')->updateOrInsert(
                ['slug'=>$t['slug']],
                array_merge($t, ['updated_at'=>now(), 'created_at'=>DB::raw('COALESCE(created_at, NOW())')])
            );
        }
    }
}
