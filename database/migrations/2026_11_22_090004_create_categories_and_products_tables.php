<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('name_en');
            $table->string('name_km')->nullable();
            $table->string('slug');
            $table->text('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('name_en');
            $table->string('name_km')->nullable();
            $table->string('slug');
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_km')->nullable();
            $table->string('product_type', 50)->default('standard'); // standard, food, beverage, retail, service
            $table->decimal('base_price', 12, 2)->default(0.00);
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->text('primary_image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('track_inventory')->default(false);
            $table->decimal('stock_quantity', 12, 2)->default(0.00);
            $table->decimal('low_stock_threshold', 12, 2)->default(5.00);
            $table->string('unit', 50)->default('item'); // item, kg, cup, plate
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['business_id', 'is_available', 'sort_order']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->string('name_en', 100);
            $table->string('name_km', 100)->nullable();
            $table->decimal('price_adjustment', 12, 2)->default(0.00);
            $table->string('sku', 100)->nullable();
            $table->decimal('stock_quantity', 12, 2)->default(0.00);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('product_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('name_en', 100);
            $table->string('name_km', 100)->nullable();
            $table->decimal('price', 12, 2)->default(0.00);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('product_addon_pivot', function (Blueprint $table) {
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignUuid('addon_id')->constrained('product_addons')->onDelete('cascade');
            $table->primary(['product_id', 'addon_id']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->text('image_url');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_addon_pivot');
        Schema::dropIfExists('product_addons');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
