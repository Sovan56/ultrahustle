<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubcategorySeeder extends Seeder
{
    public function run(): void
    {
        // Fetch type ids
        $typeIdDigital = DB::table('product_types')->where('slug','digital-product')->value('id');
        $typeIdCourse  = DB::table('product_types')->where('slug','course')->value('id');
        $typeIdService = DB::table('product_types')->where('slug','services')->value('id');

        if (!$typeIdDigital || !$typeIdCourse || !$typeIdService) {
            $this->command?->warn('Product types missing — run ProductTypeSeeder first.');
            return;
        }

        $rows = [
            // Digital Product
            ['product_type_id'=>$typeIdDigital,'name'=>'Landing Pages','slug'=>'landing-pages','is_active'=>1],
            ['product_type_id'=>$typeIdDigital,'name'=>'Email Templates','slug'=>'email-templates','is_active'=>1],
            ['product_type_id'=>$typeIdDigital,'name'=>'UI Kits','slug'=>'ui-kits','is_active'=>1],

            // Course
            ['product_type_id'=>$typeIdCourse,'name'=>'Web Development','slug'=>'web-development','is_active'=>1],
            ['product_type_id'=>$typeIdCourse,'name'=>'Design Basics','slug'=>'design-basics','is_active'=>1],
            ['product_type_id'=>$typeIdCourse,'name'=>'Marketing','slug'=>'marketing','is_active'=>1],

            // Services
            ['product_type_id'=>$typeIdService,'name'=>'Web Design','slug'=>'web-design','is_active'=>1],
            ['product_type_id'=>$typeIdService,'name'=>'SEO','slug'=>'seo','is_active'=>1],
            ['product_type_id'=>$typeIdService,'name'=>'Copywriting','slug'=>'copywriting','is_active'=>1],
        ];

        foreach ($rows as $r) {
            DB::table('product_subcategories')->updateOrInsert(
                ['product_type_id'=>$r['product_type_id'], 'slug'=>$r['slug']],
                array_merge($r, ['updated_at'=>now(), 'created_at'=>DB::raw('COALESCE(created_at, NOW())')])
            );
        }
    }
}
