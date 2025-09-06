<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_insights', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('product_id');
            $t->date('date'); // daily bucket (YYYY-MM-DD)
            $t->unsignedInteger('views')->default(0);
            $t->unsignedInteger('clicks')->default(0);
            $t->unsignedInteger('impressions')->default(0); // keep 0 for now unless you add IO tracking
            $t->timestamps();

            $t->unique(['product_id', 'date']);
            $t->index(['date']);
            $t->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_insights');
    }
};
