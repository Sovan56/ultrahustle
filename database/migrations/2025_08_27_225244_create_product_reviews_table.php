<?php
// database/migrations/2025_08_28_000002_create_product_reviews_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('rating_number'); // 1..5
            $table->text('review')->nullable();
            $table->json('images')->nullable(); // store paths if any
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('product_reviews'); }
};
