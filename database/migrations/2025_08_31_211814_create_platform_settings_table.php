<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Defaults: 5% platform fee, 18% GST
        \DB::table('platform_settings')->insert([
            ['key' => 'platform_fee_percent', 'value' => '5',  'created_at'=>now(),'updated_at'=>now()],
            ['key' => 'gst_percent',           'value' => '18', 'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
    public function down(): void {
        Schema::dropIfExists('platform_settings');
    }
};
