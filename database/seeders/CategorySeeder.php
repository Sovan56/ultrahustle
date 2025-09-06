<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder {
    public function run(): void {
        $digitalId = DB::table('product_types')->where('name','Digital Product')->value('id');
        $serviceId = DB::table('product_types')->where('name','Service')->value('id');
        $courseId  = DB::table('product_types')->where('name','Course')->value('id');

        $cats = [
            [$digitalId, 'Templates' => ['Landing Pages','Email Templates']],
            [$digitalId, 'Software'  => ['SaaS Scripts','Plugins']],
            [$serviceId, 'Design'    => ['Logo','UI/UX']],
            [$courseId , 'Programming'=> ['PHP','JavaScript']],
        ];

        foreach ($cats as $row) {
            $typeId = $row[0];
            foreach (array_slice($row,1) as $name => $subs) {
                $catId = DB::table('product_categories')->updateOrInsert(
                    ['product_type_id'=>$typeId, 'slug'=>Str::slug($name)],
                    ['name'=>$name, 'is_active'=>1, 'created_at'=>now(), 'updated_at'=>now()]
                );

                $catId = DB::table('product_categories')->where('product_type_id',$typeId)->where('slug',Str::slug($name))->value('id');
                foreach ($subs as $s) {
                    DB::table('product_subcategories')->updateOrInsert(
                        ['product_category_id'=>$catId, 'slug'=>Str::slug($s)],
                        ['name'=>$s, 'is_active'=>1, 'created_at'=>now(), 'updated_at'=>now()]
                    );
                }
            }
        }
    }
}
