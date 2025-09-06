<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();    // Digital Product, Service, Course
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete(); // directly under type
            $table->string('name');
            $table->string('slug');
            $table->string('icon_class', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['product_type_id','slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // your session user id maps to users.unique_id (string)
            $table->string('user_id'); // stores users.unique_id

            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_subcategory_id')->nullable()->constrained()->nullOnDelete();

            // REPLACE currencies -> countries
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();

            $table->string('name');
            $table->json('urls', 255)->nullable();
            $table->boolean('uses_ai')->default(false);
            $table->boolean('has_team')->default(false);
            $table->longText('description')->nullable();
            $table->json('images')->nullable();
            $table->json('files')->nullable();

            // initial status (will be altered below)
            $table->enum('status', ['published','canceled','completed'])->default('published');

            // quick flag
            $table->boolean('is_boosted')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('product_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('tier', ['basic','standard','premium']);

            // REPLACE currencies -> countries (nullable ok)
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();

            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('delivery_days')->default(0);
            $table->text('details')->nullable();
            $table->timestamps();

            $table->unique(['product_id','tier']);
        });

        Schema::create('product_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('faq_heading', 255)->nullable();
            $table->string('question');
            $table->text('faq_answer')->nullable();
            $table->timestamps();
        });

        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('buyer_name', 191)->nullable();
            $table->json('delivery_files')->nullable();
            $table->timestamp('canceled_at')->nullable();

            // REPLACE currencies -> countries
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            // initial status (will be altered below)
            $table->enum('status', ['completed','canceled','in_progress'])->default('completed');

            $table->timestamps();

            $table->index(['product_id','status']);
        });

         Schema::create('boost_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->unsignedInteger('days');                // how long the boost runs
            $table->decimal('price_usd', 12, 2);            // charge in USD
            $table->text('description')->nullable();        // plan details
            $table->boolean('is_active')->default(true);    // toggle
            $table->timestamps();
        });



        Schema::create('product_boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('user_id'); // users.unique_id who boosted
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();

            // REPLACE currencies -> countries

            $table->unsignedInteger('days');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id','is_active','end_at']);
        });

        // ---- ENUM corrections / backfills (unchanged logic) ----
        DB::table('products')
            ->whereNotIn('status', ['published','unpublished','unlisted'])
            ->update(['status' => 'published']);

        DB::table('product_orders')
            ->whereNotIn('status', ['new','in_progress','completed','canceled'])
            ->update(['status' => 'new']);

        // Use raw ALTER to avoid requiring doctrine/dbal
        DB::statement(
            "ALTER TABLE products
             MODIFY COLUMN status ENUM('published','unpublished','unlisted') NOT NULL DEFAULT 'published'"
        );

        DB::statement(
            "ALTER TABLE product_orders
             MODIFY COLUMN status ENUM('new','in_progress','completed','canceled') NOT NULL DEFAULT 'new'"
        );
    }

    public function down(): void {
        Schema::dropIfExists('product_boosts');
        Schema::dropIfExists('boost_plans');
        Schema::dropIfExists('product_orders');
        Schema::dropIfExists('product_faqs');
        Schema::dropIfExists('product_pricings');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_subcategories');
        Schema::dropIfExists('product_types');

        // IMPORTANT: Do NOT drop 'countries' here; it’s a shared table.
        // (Removed: Schema::dropIfExists('currencies'))
    }
};
