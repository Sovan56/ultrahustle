<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // normalized 1:1 pair — always store the smaller id in user_one_id
            $table->unsignedBigInteger('user_one_id');
            $table->unsignedBigInteger('user_two_id');

            // pointer to last message (FK will be added in a later migration)
            $table->unsignedBigInteger('last_message_id')->nullable()->index();

            // meta json: {"from_service":true,"origin_product_id":123}
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);

            $table->foreign('user_one_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_two_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
