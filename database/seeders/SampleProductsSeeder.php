<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Country;              // ⬅️ use countries instead of currencies
use App\Models\ProductType;
use App\Models\ProductSubcategory;
use App\Models\Product;
use App\Models\ProductPricing;
use App\Models\ProductFaq;
use App\Models\ProductOrder;
use App\Models\ProductOrderStage;

class SampleProductsSeeder extends Seeder
{
    public function run(): void
    {
        $userId = '1';
        $inCountry = Country::where('code', 'IN')->first()
            ?: Country::where('currency', 'INR')->first();

        $usCountry = Country::where('code', 'US')->first()
            ?: Country::where('currency', 'USD')->first()
            ?: $inCountry;

        $inCountryId = $inCountry?->id;
        $usCountryId = $usCountry?->id ?: $inCountryId;

        // -------- Resolve product types/subcategories --------
        $digitalTypeId = ProductType::where('slug','digital')->value('id')
            ?? ProductType::where('name','like','%digital%')->value('id');

        $serviceTypeId = ProductType::where('slug','service')->value('id')
            ?? ProductType::where('name','like','%service%')->value('id');

        $templatesSubId = ProductSubcategory::where('product_type_id',$digitalTypeId)->where('name','Templates')->value('id')
            ?? ProductSubcategory::where('product_type_id',$digitalTypeId)->value('id');

        $designSubId = ProductSubcategory::where('product_type_id',$serviceTypeId)->where('name','Design')->value('id')
            ?? ProductSubcategory::where('product_type_id',$serviceTypeId)->value('id');

        // --- DIGITAL PRODUCT -----------------------------------------------------------------
        $p = Product::updateOrCreate(
            ['user_id' => (string)$userId, 'name' => 'Email Template Pack'],
            [
                'product_type_id'        => $digitalTypeId,
                'product_subcategory_id' => $templatesSubId,
                'country_id'             => $inCountryId,  // ⬅️ was currency_id
                'uses_ai'                => false,
                'has_team'               => true,
                'description'            => '<p>High-converting email templates for SaaS.</p>',
                'images'                 => [],   // fine for seed
                'files'                  => [],
                'status'                 => 'published',
                'is_boosted'             => false,
            ]
        );

        // pricings
        if ($p) {
            ProductPricing::updateOrCreate(
                ['product_id' => $p->id, 'tier' => 'basic'],
                ['country_id' => $inCountryId, 'price' => 999,  'delivery_days' => 0, 'details' => '10 templates']
            );
            ProductPricing::updateOrCreate(
                ['product_id' => $p->id, 'tier' => 'standard'],
                ['country_id' => $inCountryId, 'price' => 1499, 'delivery_days' => 0, 'details' => '25 templates']
            );
            ProductPricing::updateOrCreate(
                ['product_id' => $p->id, 'tier' => 'premium'],
                ['country_id' => $inCountryId, 'price' => 2499, 'delivery_days' => 0, 'details' => '50 templates + variants']
            );

            ProductFaq::updateOrCreate(
                ['product_id'=>$p->id, 'question'=>'Do I get updates?'],
                ['faq_answer'=>'Yes, lifetime updates for minor tweaks.']
            );
            ProductFaq::updateOrCreate(
                ['product_id'=>$p->id, 'question'=>'License?'],
                ['faq_answer'=>'Single brand, unlimited campaigns.']
            );

            // one completed order, one new
            $oCompleted = ProductOrder::updateOrCreate(
                ['product_id'=>$p->id, 'buyer_name'=>'Acme Inc'],
                ['status'=>'completed', 'amount'=>1499, 'country_id'=>$inCountryId] // ⬅️ was currency_id
            );
            if ($oCompleted) {
                ProductOrderStage::updateOrCreate(
                    ['order_id'=>$oCompleted->id, 'position'=>0],
                    ['title'=>'Provision Files','notes'=>'Delivered pack','status'=>'done']
                );
            }

            $oNew = ProductOrder::updateOrCreate(
                ['product_id'=>$p->id, 'buyer_name'=>'Beta LLC'],
                ['status'=>'new', 'amount'=>999, 'country_id'=>$inCountryId] // ⬅️ was currency_id
            );
        }

        // --- SERVICE PRODUCT (optional demo) --------------------------------------------------
        if ($serviceTypeId && $designSubId && $usCountryId) {
            $sp = Product::updateOrCreate(
                ['user_id'=>(string)$userId, 'name'=>'Brand Design Sprint'],
                [
                    'product_type_id'        => $serviceTypeId,
                    'product_subcategory_id' => $designSubId,
                    'country_id'             => $usCountryId,     // ⬅️ was currency_id
                    'uses_ai'                => true,
                    'has_team'               => true,
                    'description'            => '<p>One-week sprint to refresh your brand.</p>',
                    'images'                 => [],
                    'files'                  => [],
                    'status'                 => 'unlisted',
                    'is_boosted'             => false,
                ]
            );

            if ($sp) {
                ProductPricing::updateOrCreate(
                    ['product_id'=>$sp->id, 'tier'=>'basic'],
                    ['country_id'=>$usCountryId, 'price'=>499, 'delivery_days'=>7, 'details'=>'Logo polish'] // ⬅️ was currency_id
                );

                $svcOrder = ProductOrder::updateOrCreate(
                    ['product_id'=>$sp->id, 'buyer_name'=>'Gamma Studios'],
                    ['status'=>'in_progress', 'amount'=>499, 'country_id'=>$usCountryId] // ⬅️ was currency_id
                );

                if ($svcOrder) {
                    // Proper columns: order_id + position
                    ProductOrderStage::updateOrCreate(
                        ['order_id'=>$svcOrder->id, 'position'=>0],
                        ['title'=>'Kickoff','notes'=>'Discovery call','status'=>'in_progress']
                    );
                    ProductOrderStage::updateOrCreate(
                        ['order_id'=>$svcOrder->id, 'position'=>1],
                        ['title'=>'Wireframes','notes'=>'Low-fidelity','status'=>'pending']
                    );
                    ProductOrderStage::updateOrCreate(
                        ['order_id'=>$svcOrder->id, 'position'=>2],
                        ['title'=>'Final Design','notes'=>'Deliver package','status'=>'pending']
                    );
                }
            }
        }
    }
}
